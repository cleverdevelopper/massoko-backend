<?php

    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class PendingDeviceMessagesEntity
    {
        public $id;
        public $message_id;
        public $user_device_id;
        public $delivered;
        public $delivered_at;
        public $created_at;

        public function cadastrar() {
            $this->id = (new Database('pending_device_messages'))->insert([
                'message_id'     => $this->message_id,
                'user_device_id' => $this->user_device_id,
                'delivered'      => $this->delivered ?? 0,
                'delivered_at'   => $this->delivered_at,
                'created_at'     => date('Y-m-d H:i:s')
            ]);
            return $this->id;
        }

        public static function getPendingMessages($userDeviceId) {
            return (new Database('pending_device_messages'))->select('user_device_id = '.$userDeviceId.' AND delivered = 0', 'created_at ASC');
        }

        public function marcarComoEntregue() {
            $this->delivered = 1;
            $this->delivered_at = date('Y-m-d H:i:s');
            return (new Database('pending_device_messages'))->update('id = '.$this->id, [
                'delivered'    => $this->delivered,
                'delivered_at' => $this->delivered_at
            ]);
        }
    }
?>
