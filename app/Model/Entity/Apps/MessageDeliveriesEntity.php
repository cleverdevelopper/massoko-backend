<?php

    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class MessageDeliveriesEntity
    {
        public $id;
        public $message_id;
        public $user_device_id;
        public $delivered_at;
        public $read_at;
        public $created_at;

        public function cadastrar() {
            $this->id = (new Database('message_deliveries'))->insert([
                'message_id'     => $this->message_id,
                'user_device_id' => $this->user_device_id,
                'delivered_at'   => $this->delivered_at,
                'read_at'        => $this->read_at,
                'created_at'     => date('Y-m-d H:i:s')
            ]);
            return $this->id;
        }

        public static function getDeliveries($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('message_deliveries'))->select($where, $order, $limit, $fields);
        }

        public function marcarComoEntregue() {
            $this->delivered_at = date('Y-m-d H:i:s');
            return (new Database('message_deliveries'))->update('id = '.$this->id, [
                'delivered_at' => $this->delivered_at
            ]);
        }

        public function marcarComoLida() {
            $this->read_at = date('Y-m-d H:i:s');
            // Ensure delivered_at is also set if read
            if (!$this->delivered_at) {
                $this->delivered_at = $this->read_at;
            }
            return (new Database('message_deliveries'))->update('id = '.$this->id, [
                'delivered_at' => $this->delivered_at,
                'read_at'      => $this->read_at
            ]);
        }
    }
?>
