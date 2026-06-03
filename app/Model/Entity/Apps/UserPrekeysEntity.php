<?php
    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class UserPrekeysEntity
    {
        public $id;
        public $user_id;
        public $prekey_id;
        public $public_key;
        public $used;
        public $created_at;

        public function cadastrar() {
            $db = new Database('user_prekeys');
            
            // Check if this prekey_id already exists for this user to avoid UNIQUE KEY constraint violation
            $exists = $db->select('user_id = ' . $this->user_id . ' AND prekey_id = ' . $this->prekey_id)->fetchObject(self::class);
            
            if ($exists) {
                // UPSERT: Update existing prekey
                $db->update('id = ' . $exists->id, [
                    'public_key' => $this->public_key,
                    'used'       => $this->used ?? 0,
                    'created_at' => $this->created_at ?? date('Y-m-d H:i:s')
                ]);
                $this->id = $exists->id;
                return $this->id;
            }

            // Insert new prekey
            $this->id = $db->insert([
                'user_id'    => $this->user_id,
                'prekey_id'  => $this->prekey_id,
                'public_key' => $this->public_key,
                'used'       => $this->used ?? 0,
                'created_at' => $this->created_at ?? date('Y-m-d H:i:s')
            ]);
            return $this->id;
        }

        public static function getUserPrekeys($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('user_prekeys'))->select($where, $order, $limit, $fields);
        }

        public static function getUserPrekeyById($id) {
            return self::getUserPrekeys('id = ' . $id)->fetchObject(self::class);
        }

        public static function getAvailablePrekey($user_id) {
            return self::getUserPrekeys('user_id = ' . $user_id . ' AND used = 0', null, 1)->fetchObject(self::class);
        }

        public static function getPrekeysByUserId($user_id) {
            return self::getUserPrekeys('user_id = ' . $user_id);
        }

        public function actualizar() {
            return (new Database('user_prekeys'))->update('id = ' . $this->id, [
                'user_id'    => $this->user_id,
                'prekey_id'  => $this->prekey_id,
                'public_key' => $this->public_key,
                'used'       => $this->used
            ]);
        }

        public function marcarComoUsada() {
            return (new Database('user_prekeys'))->update('id = ' . $this->id, [
                'used' => true
            ]);
        }

        /**
         * Método responsável por contar prekeys disponíveis para um usuário
         */
        public static function getAvailableCount($userId) {
            return (int)(new Database('user_prekeys'))->select('user_id = ' . (int)$userId . ' AND used = 0', null, null, 'COUNT(*) as total')->fetchObject()->total;
        }
    }
?>
