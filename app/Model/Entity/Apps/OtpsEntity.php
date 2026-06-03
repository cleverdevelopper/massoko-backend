<?php
    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class OtpsEntity{
        public $id;
        public $user_id;
        public $code;
        public $expires_at;
        public $verified;
        public $created_at;
                    
        public  function cadastrar(){
            $this->created_at = date('Y-m-d H:i:s');
            $this->id = (new Database('otps'))->insert([
                'user_id'                     => $this->user_id,
                'code'                        => $this->code,
                'expires_at'                  => $this->expires_at,
                'verified'                    => $this->verified,
                'created_at'                  => $this->created_at,
            ]);
            return $this->id;
        }

        public static function getOtps($where = null, $order = null, $limit = null, $fields = "*"){
            return (new Database('otps'))->select($where, $order, $limit, $fields);
        }

        public static function getOtpsByUserId($id){
            return self::getOtps('user_id = '.$id)->fetchObject(self::class);
        }

        public  function actualizar(){
            return (new Database('otps'))->update('id = '.$this->id, [
                'user_id'                     => $this->user_id,
                'code'                        => $this->code,
                'expires_at'                  => $this->expires_at,
                'verified'                    => $this->verified,
                'created_at'                  => $this->created_at,
            ]);
        }

    }

?>