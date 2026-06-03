<?php

    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class DeviceSessionsEntity
    {
        public $id;
        public $source_device_id;
        public $target_device_id;
        public $session_data;
        public $created_at;
        public $updated_at;

        public function cadastrar() {
            $this->id = (new Database('device_sessions'))->insert([
                'source_device_id' => $this->source_device_id,
                'target_device_id' => $this->target_device_id,
                'session_data'     => $this->session_data,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s')
            ]);
            return $this->id;
        }

        public static function getSessions($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('device_sessions'))->select($where, $order, $limit, $fields);
        }

        public static function getSessionByDevices($sourceDeviceId, $targetDeviceId) {
            return self::getSessions('source_device_id = '.$sourceDeviceId.' AND target_device_id = '.$targetDeviceId)->fetchObject(self::class);
        }

        public function actualizar() {
            return (new Database('device_sessions'))->update('id = '.$this->id, [
                'session_data' => $this->session_data,
                'updated_at'   => date('Y-m-d H:i:s')
            ]);
        }
        
        public static function resetSessions($sourceDeviceId) {
            return (new Database('device_sessions'))->delete('source_device_id = '.$sourceDeviceId);
        }
    }
?>
