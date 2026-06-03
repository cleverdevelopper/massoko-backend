<?php

    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class SignedPrekeyHistoryEntity
    {
        public $id;
        public $user_device_id;
        public $signed_prekey_id;
        public $public_key;
        public $signature;
        public $created_at;

        public function cadastrar() {
            $this->id = (new Database('signed_prekey_history'))->insert([
                'user_device_id'   => $this->user_device_id,
                'signed_prekey_id' => $this->signed_prekey_id,
                'public_key'       => $this->public_key,
                'signature'        => $this->signature,
                'created_at'       => date('Y-m-d H:i:s')
            ]);
            return $this->id;
        }

        public static function getHistory($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('signed_prekey_history'))->select($where, $order, $limit, $fields);
        }
    }
?>
