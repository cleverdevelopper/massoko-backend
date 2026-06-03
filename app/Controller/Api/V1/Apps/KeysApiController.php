<?php
    namespace App\Controller\Api\V1\Apps;
    use App\Controller\Api\Api;
    use App\Model\Entity\Apps\UserDevicesEntity;
    use App\Model\Entity\Apps\DevicePrekeysEntity;
    use App\DatabaseManager\Database;
    use App\Http\Response;
    use Exception;

    class KeysApiController extends Api {

        // ============================================================
        // POST /api/v1/keys
        // Armazena (ou actualiza) as chaves públicas do dispositivo do utilizador
        // ============================================================
        public static function uploadKeys($request) {

            // 1. Autenticação
            $loggedUser = $request->user;
            if (!$loggedUser) {
                return self::error('Usuário não autenticado', 401);
            }

            // 2. Obter dados do request
            $postVars  = $request->getPostVars();
            $deviceId       = $postVars['device_id'] ?? null;
            $identityKey    = $postVars['identity_key']  ?? null;
            $registrationId = $postVars['registration_id'] ?? null;
            $signedPrekeyId = $postVars['signed_prekey_id'] ?? 0;
            $signedPrekey   = $postVars['signed_prekey'] ?? null;
            $signature      = $postVars['signature']     ?? null;
            $prekeys        = $postVars['prekeys']        ?? [];

            // 3. Validação dos campos obrigatórios
            if (!$deviceId || !$identityKey || !$signedPrekey || !$signature) {
                return self::error(
                    'Os campos device_id, identity_key, signed_prekey e signature são obrigatórios',
                    400
                );
            }

            if (!is_array($prekeys) || empty($prekeys)) {
                return self::error('É necessário enviar pelo menos uma prekey', 400);
            }

            // 4. Persistência dentro de uma transação
            $db = new Database();
            try {
                $db->beginTransaction();

                // 4a. UPSERT em user_devices
                $existingDevice = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);

                if ($existingDevice) {
                    $existingDevice->registration_id = $registrationId ?? $existingDevice->registration_id;
                    $existingDevice->identity_key    = $identityKey;
                    $existingDevice->signed_prekey_id = $signedPrekeyId;
                    $existingDevice->signed_prekey   = $signedPrekey;
                    $existingDevice->signed_prekey_signature = $signature;
                    $existingDevice->actualizar();
                    $deviceIdDb = $existingDevice->id;
                } else {
                    $obKey = new UserDevicesEntity();
                    $obKey->user_id                   = $loggedUser->id;
                    $obKey->device_id                 = $deviceId;
                    $obKey->platform                  = 'android'; // ou obter do request
                    $obKey->identity_key              = $identityKey;
                    $obKey->registration_id           = $registrationId;
                    $obKey->signed_prekey_id          = $signedPrekeyId;
                    $obKey->signed_prekey             = $signedPrekey;
                    $obKey->signed_prekey_signature   = $signature;
                    $deviceIdDb = $obKey->cadastrar();
                }

                // 4b. Inserir as prekeys
                $inserted = 0;
                foreach ($prekeys as $prekey) {
                    $prekeyId  = $prekey['id']  ?? null;
                    $publicKey = $prekey['key'] ?? null;

                    if ($prekeyId === null || !$publicKey) {
                        continue;
                    }

                    $obPrekey = new DevicePrekeysEntity();
                    $obPrekey->device_id   = $deviceIdDb;
                    $obPrekey->prekey_id   = (int)$prekeyId;
                    $obPrekey->public_key  = $publicKey;
                    $obPrekey->used        = 0;
                    $obPrekey->cadastrar();
                    $inserted++;
                }

                $db->commit();

                return self::success('Chaves registadas com sucesso', [
                    'prekeys_uploaded' => $inserted
                ], 201);

            } catch (Exception $e) {
                $db->rollBack();
                return self::error('Falha ao registar chaves: ' . $e->getMessage(), 500);
            }
        }

        // ============================================================
        // GET /api/v1/users/{id}/keys
        // Devolve os bundles de chaves públicas para cifrar uma sessão
        // com TODOS os dispositivos ativos do utilizador
        // ============================================================
        public static function getKeyBundle($request, $id = null) {

            // 1. Autenticação
            $loggedUser = $request->user;
            if (!$loggedUser) {
                return new Response(401, ['error' => 'Usuário não autenticado'], 'application/json');
            }

            $targetUserId = $id;
            if (!$targetUserId || !is_numeric($targetUserId)) {
                return new Response(400, ['error' => 'ID de utilizador inválido'], 'application/json');
            }

            $devices = UserDevicesEntity::getDevices('user_id = ' . (int)$targetUserId . ' AND is_active = 1');
            
            $bundles = [];
            $db = new Database();
            
            try {
                $db->beginTransaction();
                
                while ($device = $devices->fetchObject(UserDevicesEntity::class)) {
                    $prekey = DevicePrekeysEntity::getAvailablePrekey($device->id);
                    
                    if ($prekey) {
                        $prekey->marcarComoUsada();
                        $prekeyData = [
                            'id' => (int)$prekey->prekey_id,
                            'public_key' => $prekey->public_key
                        ];
                    } else {
                        $prekeyData = null; // Falta de prekeys, o cliente deve usar só a signed_prekey
                    }
                    
                    $bundles[] = [
                        'device_id' => (int)$device->device_id,
                        'registration_id' => (int)$device->registration_id,
                        'identity_key' => $device->identity_key,
                        'signed_prekey_id' => (int)$device->signed_prekey_id,
                        'signed_prekey' => $device->signed_prekey,
                        'signed_prekey_signature' => $device->signed_prekey_signature,
                        'prekey' => $prekeyData
                    ];
                }
                
                $db->commit();
                
                if (empty($bundles)) {
                    return new Response(404, ['error' => 'User has no active devices or keys'], 'application/json');
                }
                
                return new Response(200, ['bundles' => $bundles], 'application/json');
                
            } catch (Exception $e) {
                $db->rollBack();
                return new Response(500, ['error' => 'Falha ao obter bundle de chaves: ' . $e->getMessage()], 'application/json');
            }
        }

        // ============================================================
        // POST /api/v1/keys/prekeys
        // Upload de prekeys adicionais
        // ============================================================
        public static function uploadMorePrekeys($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $deviceId = $postVars['device_id'] ?? null;
            $prekeys  = $postVars['prekeys'] ?? [];

            if (!$deviceId) return self::error('device_id obrigatório', 400);

            $device = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);
            if (!$device) return self::error('Dispositivo não registado', 404);

            if (!is_array($prekeys) || empty($prekeys)) return self::error('É necessário enviar pelo menos uma prekey', 400);

            $db = new Database();
            try {
                $db->beginTransaction();

                $inserted = 0;
                foreach ($prekeys as $prekey) {
                    $prekeyId  = $prekey['id'] ?? null;
                    $publicKey = $prekey['key'] ?? null;

                    if ($prekeyId === null || !$publicKey) continue;

                    $obPrekey = new DevicePrekeysEntity();
                    $obPrekey->device_id = $device->id;
                    $obPrekey->prekey_id = (int)$prekeyId;
                    $obPrekey->public_key = $publicKey;
                    $obPrekey->used = 0;
                    $obPrekey->cadastrar();
                    $inserted++;
                }

                $db->commit();
                return self::success('Prekeys adicionadas', ['prekeys_uploaded' => $inserted], 201);
            } catch (Exception $e) {
                $db->rollBack();
                return self::error('Falha ao adicionar prekeys: ' . $e->getMessage(), 500);
            }
        }

        public static function getKeyStatus($request) {
            $loggedUser = $request->user;
            $queryParams = $request->getQueryParams();
            $deviceId = $queryParams['device_id'] ?? null;

            if (!$deviceId) return self::error('device_id obrigatório', 400);

            $device = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);
            if (!$device) return self::error('Dispositivo não encontrado', 404);

            $prekeyCount = DevicePrekeysEntity::getAvailableCount($device->id);

            return [
                'success' => true,
                'status' => [
                    'has_identity_key' => !empty($device->identity_key),
                    'prekey_count'     => $prekeyCount,
                    'last_rotation'    => $device->updated_at,
                    'needs_replenishment' => $prekeyCount < 10,
                    'needs_rotation'      => (strtotime($device->updated_at) < strtotime('-7 days'))
                ]
            ];
        }
    }
?>
