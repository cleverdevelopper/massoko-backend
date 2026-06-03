<?php

    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class DevicePrekeysEntity
    {
        public $id;
        public $device_id;
        public $prekey_id;
        public $public_key;
        public $used;
        public $created_at;

        public function cadastrar() {
            $this->id = (new Database('device_prekeys'))->insert([
                'device_id'  => $this->device_id,
                'prekey_id'  => $this->prekey_id,
                'public_key' => $this->public_key,
                'used'       => $this->used ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return $this->id;
        }

        public static function getPrekeys($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('device_prekeys'))->select($where, $order, $limit, $fields);
        }

        public static function getAvailablePrekey($deviceId) {
            return self::getPrekeys('device_id = '.$deviceId.' AND used = 0', 'id ASC', 1)->fetchObject(self::class);
        }

        public static function getAvailableCount($deviceId) {
            $result = (new Database('device_prekeys'))->select('device_id = '.$deviceId.' AND used = 0', null, null, 'COUNT(*) as count')->fetchObject();
            return $result->count ?? 0;
        }

        public function marcarComoUsada() {
            $this->used = 1;
            return (new Database('device_prekeys'))->update('id = '.$this->id, [
                'used' => 1
            ]);
        }
    }
?>
