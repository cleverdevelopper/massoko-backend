<?php

    namespace App\Controller\Api\V1\Apps;
    use App\Controller\Api\Api;
    use App\Model\Entity\Apps\UserDevicesEntity;
    use App\Model\Entity\Apps\DeviceSessionsEntity;
    use App\Model\Entity\Apps\SignedPrekeyHistoryEntity;
    use App\DatabaseManager\Database;
    use Exception;

    class SignalApiController extends Api {

        // ============================================================
        // POST /api/v1/signal/register-device
        // ============================================================
        public static function registerDevice($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $deviceId = $postVars['device_id'] ?? null;
            $platform = $postVars['platform'] ?? 'android';
            $deviceName = $postVars['device_name'] ?? null;

            if (!$deviceId) return self::error('device_id obrigatório', 400);

            $device = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);
            if (!$device) {
                $device = new UserDevicesEntity();
                $device->user_id = $loggedUser->id;
                $device->device_id = $deviceId;
                $device->platform = $platform;
                $device->device_name = $deviceName;
                // Wait for uploadKeys to populate the keys, or initialize empty strings
                $device->registration_id = 0;
                $device->identity_key = '';
                $device->signed_prekey_id = 0;
                $device->signed_prekey = '';
                $device->signed_prekey_signature = '';
                $device->cadastrar();
            } else {
                $device->is_active = 1;
                $device->actualizar();
            }

            return self::success('Dispositivo registado com sucesso', ['device_id' => $deviceId], 200);
        }

        // ============================================================
        // POST /api/v1/signal/session/save
        // ============================================================
        public static function saveSession($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $sourceDeviceId = $postVars['source_device_id'] ?? null;
            $targetDeviceId = $postVars['target_device_id'] ?? null;
            $sessionData = $postVars['session_data'] ?? null;

            if (!$sourceDeviceId || !$targetDeviceId || !$sessionData) {
                return self::error('Parâmetros obrigatórios em falta', 400);
            }

            $session = DeviceSessionsEntity::getSessionByDevices($sourceDeviceId, $targetDeviceId);
            if ($session) {
                $session->session_data = $sessionData;
                $session->actualizar();
            } else {
                $session = new DeviceSessionsEntity();
                $session->source_device_id = $sourceDeviceId;
                $session->target_device_id = $targetDeviceId;
                $session->session_data = $sessionData;
                $session->cadastrar();
            }

            return self::success('Sessão guardada com sucesso', [], 200);
        }

        // ============================================================
        // GET /api/v1/signal/session/load
        // ============================================================
        public static function loadSession($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $queryParams = $request->getQueryParams();
            $sourceDeviceId = $queryParams['source_device_id'] ?? null;
            $targetDeviceId = $queryParams['target_device_id'] ?? null;

            if (!$sourceDeviceId || !$targetDeviceId) {
                return self::error('Parâmetros obrigatórios em falta', 400);
            }

            $session = DeviceSessionsEntity::getSessionByDevices($sourceDeviceId, $targetDeviceId);
            if ($session) {
                return self::success('Sessão carregada', ['session_data' => $session->session_data], 200);
            }

            return self::error('Sessão não encontrada', 404);
        }

        // ============================================================
        // DELETE /api/v1/signal/session/reset
        // ============================================================
        public static function resetSessions($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $sourceDeviceId = $postVars['source_device_id'] ?? null;

            if (!$sourceDeviceId) {
                return self::error('Parâmetros obrigatórios em falta', 400);
            }

            DeviceSessionsEntity::resetSessions($sourceDeviceId);
            return self::success('Sessões eliminadas', [], 200);
        }

        // ============================================================
        // POST /api/v1/signal/rotate-signed-prekey
        // ============================================================
        public static function rotateSignedPrekey($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $deviceId = $postVars['device_id'] ?? null;
            $signedPrekeyId = $postVars['signed_prekey_id'] ?? null;
            $signedPrekey = $postVars['signed_prekey'] ?? null;
            $signature = $postVars['signature'] ?? null;

            if (!$deviceId || !$signedPrekeyId || !$signedPrekey || !$signature) {
                return self::error('Parâmetros obrigatórios em falta', 400);
            }

            $device = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);
            if (!$device) {
                return self::error('Dispositivo não encontrado', 404);
            }

            $db = new Database();
            try {
                $db->beginTransaction();

                // Arquivar atual
                $history = new SignedPrekeyHistoryEntity();
                $history->user_device_id = $device->id;
                $history->signed_prekey_id = $device->signed_prekey_id;
                $history->public_key = $device->signed_prekey;
                $history->signature = $device->signed_prekey_signature;
                $history->cadastrar();

                // Atualizar com nova
                $device->signed_prekey_id = $signedPrekeyId;
                $device->signed_prekey = $signedPrekey;
                $device->signed_prekey_signature = $signature;
                $device->actualizar();

                $db->commit();
                return self::success('Signed Prekey rodada com sucesso', [], 200);
            } catch (Exception $e) {
                $db->rollBack();
                return self::error('Falha ao rodar prekey: ' . $e->getMessage(), 500);
            }
        }
    }
?>
