<?php

    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class UsersEntity
    {
        public $id;
        public $phone_number;
        public $name;
        public $surname;
        public $is_online;
        public $last_seen;
        public $created_at;
        public $updated_at;                    
        public $deleted_at;


        public  function cadastrar(){
            $this->id = (new Database('users'))->insert([
                'phone_number'      => $this->phone_number,
                'name'              => $this->name,
                'surname'           => $this->surname,
                'is_online'         => $this->is_online,
                'last_seen'         => $this->last_seen,
                'created_at'        => $this->created_at,
                'updated_at'        => $this->updated_at,
                'deleted_at'        => $this->deleted_at
        ]);
            return $this->id;
        }

        public static function getUsers($where = null, $order = null, $limit = null, $fields = "*"){
            return (new Database('users'))->select($where, $order, $limit, $fields);
        }

        public static function getUserById($id){
            return self::getUsers('id = '.$id)->fetchObject(self::class);
        }

        public  function actualizar(){
            return (new Database('users'))->update('id = '.$this->id, [
                'phone_number'      => $this->phone_number,
                'name'              => $this->name,
                'surname'           => $this->surname,
                'is_online'         => $this->is_online,
                'last_seen'         => $this->last_seen,
                'created_at'        => $this->created_at,
                'updated_at'        => $this->updated_at,
                'deleted_at'        => $this->deleted_at
            ]);
        }

    }
?>