<?php

    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class UserDevicesEntity
    {
        public $id;
        public $user_id;
        public $device_id;
        public $platform;
        public $device_name;
        public $registration_id;
        public $identity_key;
        public $signed_prekey_id;
        public $signed_prekey;
        public $signed_prekey_signature;
        public $current_signed_prekey_id;
        public $is_active;
        public $last_seen;
        public $created_at;
        public $updated_at;

        public function cadastrar() {
            $this->id = (new Database('user_devices'))->insert([
                'user_id'                  => $this->user_id,
                'device_id'                => $this->device_id,
                'platform'                 => $this->platform,
                'device_name'              => $this->device_name,
                'registration_id'          => $this->registration_id,
                'identity_key'             => $this->identity_key,
                'signed_prekey_id'         => $this->signed_prekey_id,
                'signed_prekey'            => $this->signed_prekey,
                'signed_prekey_signature'  => $this->signed_prekey_signature,
                'current_signed_prekey_id' => $this->current_signed_prekey_id,
                'is_active'                => $this->is_active ?? 1,
                'last_seen'                => $this->last_seen,
                'created_at'               => date('Y-m-d H:i:s'),
                'updated_at'               => date('Y-m-d H:i:s')
            ]);
            return $this->id;
        }

        public static function getDevices($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('user_devices'))->select($where, $order, $limit, $fields);
        }

        public static function getDeviceById($id) {
            return self::getDevices('id = '.$id)->fetchObject(self::class);
        }

        public static function getDeviceByUserIdAndDeviceId($userId, $deviceId) {
            return self::getDevices('user_id = '.$userId.' AND device_id = '.$deviceId)->fetchObject(self::class);
        }

        public function actualizar() {
            return (new Database('user_devices'))->update('id = '.$this->id, [
                'user_id'                  => $this->user_id,
                'device_id'                => $this->device_id,
                'platform'                 => $this->platform,
                'device_name'              => $this->device_name,
                'registration_id'          => $this->registration_id,
                'identity_key'             => $this->identity_key,
                'signed_prekey_id'         => $this->signed_prekey_id,
                'signed_prekey'            => $this->signed_prekey,
                'signed_prekey_signature'  => $this->signed_prekey_signature,
                'current_signed_prekey_id' => $this->current_signed_prekey_id,
                'is_active'                => $this->is_active,
                'last_seen'                => $this->last_seen,
                'updated_at'               => date('Y-m-d H:i:s')
            ]);
        }
    }
?>
