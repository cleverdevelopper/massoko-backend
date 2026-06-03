<?php
namespace App\Model\Entity\Apps;

use App\DatabaseManager\Database;

class UserProfilesEntity
{
    public $user_id;
    public $profile_name;
    public $profile_photo;
    public $about;
    public $profile_key;
    public $updated_at;

    public function cadastrar() {
        (new Database('user_profiles'))->insert([
            'user_id'       => $this->user_id,
            'profile_name'  => $this->profile_name,
            'profile_photo' => $this->profile_photo,
            'about'         => $this->about,
            'profile_key'   => $this->profile_key
        ]);
        return $this->user_id;
    }

    public static function getProfiles($where = null, $order = null, $limit = null, $fields = "*") {
        return (new Database('user_profiles'))->select($where, $order, $limit, $fields);
    }

    public static function getByUserId($userId) {
        return self::getProfiles('user_id = ' . (int)$userId)->fetchObject(self::class);
    }

    public function actualizar() {
        return (new Database('user_profiles'))->update('user_id = ' . (int)$this->user_id, [
            'profile_name'  => $this->profile_name,
            'profile_photo' => $this->profile_photo,
            'about'         => $this->about,
            'profile_key'   => $this->profile_key
        ]);
    }
}
