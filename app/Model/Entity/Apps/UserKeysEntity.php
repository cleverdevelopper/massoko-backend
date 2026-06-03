<?php
    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class UserKeysEntity
    {
        public $id;
        public $user_id;
        public $identity_key;
        public $registration_id;
        public $signed_prekey;
        public $signed_prekey_signature;
        public $created_at;

        public function cadastrar() {
            $this->id = (new Database('user_keys'))->insert([
                'user_id'                   => $this->user_id,
                'identity_key'              => $this->identity_key,
                'registration_id'           => $this->registration_id,
                'signed_prekey'             => $this->signed_prekey,
                'signed_prekey_signature'   => $this->signed_prekey_signature,
                'created_at'                => $this->created_at
            ]);
            return $this->id;
        }

        public static function getUserKeys($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('user_keys'))->select($where, $order, $limit, $fields);
        }

        public static function getUserKeyById($id) {
            return self::getUserKeys('id = ' . $id)->fetchObject(self::class);
        }

        public static function getUserKeyByUserId($user_id) {
            return self::getUserKeys('user_id = ' . $user_id)->fetchObject(self::class);
        }

        public function actualizar() {
            return (new Database('user_keys'))->update('id = ' . $this->id, [
                'user_id'                   => $this->user_id,
                'identity_key'              => $this->identity_key,
                'registration_id'           => $this->registration_id,
                'signed_prekey'             => $this->signed_prekey,
                'signed_prekey_signature'   => $this->signed_prekey_signature
            ]);
        }
    }
?>
