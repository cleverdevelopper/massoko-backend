<?php
    namespace App\Controller\Api\V1\Apps;
    use App\Controller\Api\Api;
    use App\Model\Entity\Apps\UsersEntity;
    use App\Model\Entity\Apps\OtpsEntity;
    use App\Model\Entity\Apps\RefreshTokensEntity;
    use App\Model\Entity\Apps\UserProfilesEntity;
    use App\DatabaseManager\Database;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class AuthenticationApiController extends Api{
        
        public static function phoneNumberRgister($request){
            $postVars       = $request->getPostVars();
            $phoneNumber    = $postVars['phoneNumber'] ?? null;

            //valida se o numero de telefone foi enviado
            if(empty($phoneNumber)){
                return self::error('Número de telefone não enviado', 400);
            }

            //valida se o numero de telefone é válido (aceita com ou sem +258 e 9 dígitos)
            if(!preg_match('/^(\+258)?[0-9]{9}$/', $phoneNumber)){
                return self::error('Número de telefone inválido', 400);
            }

            $obUser = UsersEntity::getUsers('phone_number = "'.$phoneNumber.'"')->fetchObject(UsersEntity::class);
            
            if(!$obUser){
                // 1. Cadastrar novo usuário (se não existir)
                $obUser = new UsersEntity();
                $obUser->phone_number = $phoneNumber;
                $accountId = $obUser->cadastrar();
                $message = 'Número de celular cadastrado com sucesso. OTP enviado.';
            } else {
                // Usuário já existe, apenas enviar OTP para login
                $accountId = $obUser->id;
                $message = 'Usuário encontrado. OTP de login enviado.';
            }

            // 2. Gerar OTP de 6 dígitos
            $otpCode = (string)rand(100000, 999999);

            // 3. Guardar OTP na base de dados
            $expiracaoMinutos = getenv('OTP_EXPIRACAO_MINUTOS') ?: 5;
            
            $otpEntity              = new OtpsEntity();
            $otpEntity->user_id     = $accountId;
            $otpEntity->code        = $otpCode;
            $otpEntity->expires_at  = date('Y-m-d H:i:s', strtotime('+'.$expiracaoMinutos.' minutes'));
            $otpEntity->verified    = 0;
            $otpEntity->cadastrar();

             // 4. Enviar SMS via MozeSMS
             $smsMessage = "Homeclinica: Seu codigo de verificacao e " . $otpCode . ". Nao partilhe este codigo com ninguem. Valido por " . $expiracaoMinutos . " minutos.";
             $smsSent = self::sendSMS($phoneNumber, $smsMessage);

            return [
                'success'    => true,
                'message'    => $message,
                'account_id' => (int)$accountId
            ];
        }


        private static function sendSMS($to, $message) {
            $apiUrl    = getenv('MOZESMS_API_URL');
            $apiKey    = getenv('MOZESMS_TOKEN'); // mk_...
            $apiSecret = getenv('MOZESMS_API_SECRET'); // sk_...
            $sender    = getenv('MOZESMS_SENDER');

            if (!$apiUrl || !$apiKey) return false;

            // Garantir que o número não tenha o '+' 
            $to = str_replace('+', '', $to);

            // Formato exigido pela MozeSMS: 'from' em vez de 'sender'
            $payload = [
                'to'      => $to,
                'message' => $message,
                'from'    => $sender
            ];

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-API-Key: ' . $apiKey,
                'X-API-Secret: ' . $apiSecret,
                'Content-Type: application/json'
            ]);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            return ($httpCode >= 200 && $httpCode < 300);
        }


        /**
         * Método responsável por verificar o OTP e atualizar a etapa
         */
        public static function verifyOtp($request)
        {
            $postVars  = $request->getPostVars();
            $accountId = $postVars['account_id'] ?? null;
            $otpCode   = $postVars['otp_code'] ?? null;

            if (empty($accountId) || empty($otpCode)) {
                return [
                    'error'   => 'MISSING_PARAMS',
                    'message' => 'ID da conta e código OTP são obrigatórios',
                    'etapa'   => 'TELEFONE'
                ];
            }

            // 1. Buscar a conta do usuário
            $account = UsersEntity::getUserById($accountId);
            if (!$account) {
                return [
                    'error'   => 'ACCOUNT_NOT_FOUND',
                    'message' => 'Conta não encontrada',
                    'etapa'   => 'TELEFONE'
                ];
            }

            // 2. Buscar o OTP mais recente e não usado para esta conta
            $otps = OtpsEntity::getOtps('user_id = ' . $accountId . ' AND verified = 0', 'id DESC', '1')->fetchObject(OtpsEntity::class);

            if (!$otps) {
                return [
                    'error'   => 'OTP_NOT_FOUND',
                    'message' => 'Nenhum código de verificação encontrado ou já utilizado.',
                    'etapa'   => 'TELEFONE'
                ];
            }

            // 3. Verificar se o código coincide (comparação não estrita para evitar problemas de tipo)
            if ($otps->code != $otpCode) {
                return [
                    'error'   => 'INVALID_OTP',
                    'message' => 'Código de verificação inválido.',
                    'etapa'   => 'TELEFONE'
                ];
            }

            // 4. Verificar expiração
            if (strtotime($otps->expires_at) < time()) {
                return [
                    'error'   => 'EXPIRED_OTP',
                    'message' => 'Código de verificação expirou.',
                    'etapa'   => 'TELEFONE'
                ];
            }

            // 5. Marcar OTP como usado
            $otps->verified = 1;
            $otps->actualizar();

            // 6. Verificar se o usuário já completou o perfil (Login vs Registro)
            $tokens = null;
            $user   = null;
            $etapa  = 'OTP_VALIDADO';

            if (!empty($account->name)) {
                $tokens = self::generateTokens($account);
                $profile = UserProfilesEntity::getByUserId($account->id);
                $user = [
                    'id'           => (int)$account->id,
                    'name'         => $account->name,
                    'surname'      => $account->surname,
                    'phone_number' => $account->phone_number,
                    'profile_photo'=> self::getProfilePhotoUrl($profile ? $profile->profile_photo : null),
                    'is_online'    => (int)$account->is_online,
                    'last_seen'    => $account->last_seen,
                    'created_at'   => $account->created_at
                ];
                $etapa = 'COMPLETO';
            }

            return [
                'success'    => true,
                'message'    => 'Código verificado com sucesso.',
                'account_id' => (int)$accountId,
                'etapa'      => $etapa,
                'tokens'     => $tokens,
                'user'       => $user
            ];
        }

         /**
         * Método responsável por finalizar o cadastro do usuário
         * @param Request $request
         * @return array
         */
        public static function finalizeRegistration($request)
        {
            $postVars = $request->getPostVars();
            $accountId = $postVars['account_id'] ?? null;

            if (empty($accountId)) {
                return self::error('O ID da conta é obrigatório', 400);
            }

            // 1. Buscar o usuário
            $obUser = UsersEntity::getUserById($accountId);
            if (!$obUser) {
                return self::error('Usuário não encontrado', 404);
            }

            // 2. Extrair dados do request
            $name       = $postVars['name'] ?? $obUser->name;
            $surname    = $postVars['surname'] ?? $obUser->surname;
            $publicKey  = $postVars['public_key'] ?? null;
            $avatar     = $postVars['uploaded_images']['avatar'] ?? ($postVars['avatar'] ?? null);

            // 3. Atualizar dados do usuário
            $obUser->name           = $name;
            $obUser->surname        = $surname;
            $obUser->updated_at     = date('Y-m-d H:i:s');

            // 4. Salvar alterações
            $obUser->actualizar();

            // 4b. Salvar perfil
            $obProfile = UserProfilesEntity::getByUserId($accountId);
            if (!$obProfile) {
                $obProfile = new UserProfilesEntity();
                $obProfile->user_id = $accountId;
                $obProfile->profile_name = trim($name . ' ' . $surname);
                $obProfile->profile_photo = $avatar;
                $obProfile->profile_key = $publicKey;
                $obProfile->cadastrar();
            } else {
                $obProfile->profile_name = trim($name . ' ' . $surname);
                if ($avatar !== null) $obProfile->profile_photo = $avatar;
                if ($publicKey !== null) $obProfile->profile_key = $publicKey;
                $obProfile->actualizar();
            }

            // 5. Gerar tokens de acesso
            $tokens = self::generateTokens($obUser);

            return [
                'success'    => true,
                'message'    => 'Cadastro finalizado com sucesso',
                'account_id' => (int)$accountId,
                'tokens'     => $tokens,
                'user'       => [
                    'id'           => (int)$obUser->id,
                    'name'         => $obUser->name,
                    'surname'      => $obUser->surname,
                    'phone_number' => $obUser->phone_number,
                    'profile_photo'=> self::getProfilePhotoUrl($obProfile->profile_photo),
                    'is_online'    => (int)$obUser->is_online,
                    'last_seen'    => $obUser->last_seen,
                    'created_at'   => $obUser->created_at
                ]
            ];
        }

        /**
         * Método responsável por gerar os tokens JWT (Access e Refresh)
         * @param UsersEntity $obUser
         * @return array
         */
        private static function generateTokens($obUser)
        {
            $jwtSecret = getenv('JWT_SECRET') ?: 'massoko_secret_key_2024';
            
            // Payload do Access Token (expira em 15 minutos)
            $payload = [
                'iss' => getenv('URL'),
                'aud' => getenv('URL'),
                'iat' => time(),
                'exp' => time() + 900, // 15 minutos
                'user_id' => $obUser->id
            ];

            $accessToken = JWT::encode($payload, $jwtSecret, 'HS256');

            // Gerar Refresh Token (expira em 7 dias)
            $refreshTokenValue = bin2hex(random_bytes(64));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

            $obRefreshToken = new RefreshTokensEntity();
            $obRefreshToken->user_id    = $obUser->id;
            $obRefreshToken->token      = $refreshTokenValue;
            $obRefreshToken->expires_at = $expiresAt;
            $obRefreshToken->revoked    = 0;
            $obRefreshToken->cadastrar();

            return [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshTokenValue,
                'expires_in'    => 900
            ];
        }

        /**
         * Método responsável por renovar o access token usando um refresh token
         * @param Request $request
         * @return array
         */
        public static function refreshToken($request)
        {
            $postVars = $request->getPostVars();
            $refreshTokenValue = $postVars['refresh_token'] ?? null;

            if (!$refreshTokenValue) {
                return self::error('Refresh token não enviado', 400);
            }

            // Buscar o token no banco
            $obRefreshToken = RefreshTokensEntity::getRefreshTokenByToken($refreshTokenValue);

            if (!$obRefreshToken || $obRefreshToken->revoked || strtotime($obRefreshToken->expires_at) < time()) {
                return self::error('Refresh token inválido ou expirado', 401);
            }

            // Buscar o usuário
            $obUser = UsersEntity::getUserById($obRefreshToken->user_id);
            if (!$obUser) {
                return self::error('Usuário não encontrado', 404);
            }

            // Gerar novos tokens
            $tokens = self::generateTokens($obUser);

            // Revogar o token antigo (opcional, para maior segurança - rotação de tokens)
            $obRefreshToken->revoked = 1;
            $obRefreshToken->actualizar();

            return [
                'success' => true,
                'tokens'  => $tokens
            ];
        }

        /**
         * Método responsável por retornar os dados do usuário autenticado
         * @param Request $request
         * @return array
         */
        public static function getMe($request)
        {
            // O usuário já foi injetado pelo middleware JWTAuth
            $obUser = $request->user;
            $profile = UserProfilesEntity::getByUserId($obUser->id);

            return [
                'success' => true,
                'user'    => [
                    'id'           => (int)$obUser->id,
                    'name'         => $obUser->name,
                    'surname'      => $obUser->surname,
                    'phone_number' => $obUser->phone_number,
                    'profile_photo'=> self::getProfilePhotoUrl($profile ? $profile->profile_photo : null),
                    'about'        => $profile ? $profile->about : null,
                    'is_online'    => (int)$obUser->is_online,
                    'last_seen'    => $obUser->last_seen,
                    'created_at'   => $obUser->created_at
                ]
            ];
        }

        /**
         * Método responsável por deslogar o usuário (revogar refresh token)
         * @param Request $request
         * @return array
         */
        public static function logout($request)
        {
            $postVars = $request->getPostVars();
            $refreshTokenValue = $postVars['refresh_token'] ?? null;

            if ($refreshTokenValue) {
                $obRefreshToken = RefreshTokensEntity::getRefreshTokenByToken($refreshTokenValue);
                if ($obRefreshToken) {
                    $obRefreshToken->revoked = 1;
                    $obRefreshToken->actualizar();
                }
            }

            return [
                'success' => true,
                'message' => 'Deslogado com sucesso'
            ];
        }

        /**
         * Método responsável por retornar os dados básicos de um perfil
         * @param Request $request
         * @return array
         */
        public static function getUserProfile($request)
        {
            $queryParams = $request->getQueryParams();
            $id = $queryParams['user_id'] ?? null;

            // Busca por ID se fornecido, caso contrário usa o logado. 
            // Se o ID fornecido não existir, faz fallback para o logado para evitar 404 desnecessário.
            $obUser = $id ? (UsersEntity::getUserById($id) ?: $request->user) : ($request->user ?? null);

            if (!$obUser) {
                return self::error('Usuário não encontrado', 404);
            }

            $profile = UserProfilesEntity::getByUserId($obUser->id);

            return [
                'success' => true,
                'user'    => [
                    'id'            => (int)$obUser->id,
                    'name'          => $obUser->name,
                    'surname'       => $obUser->surname,
                    'phone_number'  => $obUser->phone_number,
                    'profile_photo' => self::getProfilePhotoUrl($profile ? $profile->profile_photo : null),
                    'about'         => $profile ? $profile->about : null,
                    'is_online'     => (int)$obUser->is_online,
                    'last_seen'     => $obUser->last_seen,
                    'created_at'    => $obUser->created_at
                ]
            ];
        }

        /**
         * Método responsável por retornar a URL completa da foto de perfil
         * @param string $photo
         * @return string
         */
        private static function getProfilePhotoUrl($photo)
        {
            if (empty($photo)) {
                return null;
            }
            return getenv('URL') . '/images/avatars/' . $photo;
        }



        /**
         * Update the authenticated user's avatar / about text.
         * Accepts:  avatar (filename string)  and/or  about (string)
         */
        public static function updateProfile($request)
        {
            $obUser   = $request->user;
            if (!$obUser) return self::error('Não autenticado', 401);

            $postVars = $request->getPostVars();
            $avatar   = $postVars['uploaded_images']['avatar'] ?? ($postVars['avatar'] ?? null);
            $about    = $postVars['about'] ?? null;
            $name     = $postVars['name'] ?? null;
            $surname  = $postVars['surname'] ?? null;

            // Update users table if name/surname provided
            if ($name !== null) $obUser->name    = $name;
            if ($surname !== null) $obUser->surname = $surname;
            if ($name !== null || $surname !== null) {
                $obUser->updated_at = date('Y-m-d H:i:s');
                $obUser->actualizar();
            }

            // Upsert user_profiles
            $profile = UserProfilesEntity::getByUserId($obUser->id);
            if (!$profile) {
                $profile = new UserProfilesEntity();
                $profile->user_id      = $obUser->id;
                $profile->profile_name = trim(($name ?? $obUser->name) . ' ' . ($surname ?? $obUser->surname ?? ''));
                $profile->profile_photo = $avatar;
                $profile->about        = $about;
                $profile->cadastrar();
            } else {
                if ($name !== null || $surname !== null) {
                    $profile->profile_name = trim(($name ?? $obUser->name) . ' ' . ($surname ?? $obUser->surname ?? ''));
                }
                if ($avatar !== null) $profile->profile_photo = $avatar;
                if ($about  !== null) $profile->about         = $about;
                $profile->actualizar();
            }

            return [
                'success' => true,
                'message' => 'Perfil actualizado com sucesso',
                'user'    => [
                    'id'            => (int)$obUser->id,
                    'name'          => $obUser->name,
                    'surname'       => $obUser->surname,
                    'phone_number'  => $obUser->phone_number,
                    'profile_photo' => self::getProfilePhotoUrl($profile->profile_photo),
                    'about'         => $profile->about,
                    'is_online'     => (int)$obUser->is_online,
                    'last_seen'     => $obUser->last_seen,
                    'created_at'    => $obUser->created_at
                ]
            ];
        }

        /**
         * Método responsável por actualizar apenas o avatar do usuário
         * @param Request $request
         * @return array
         */
        public static function updateAvatar($request)
        {
            return self::updateProfile($request);
        }

    }
?>