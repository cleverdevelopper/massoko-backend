<?php
    namespace App\Controller\Api\V1\Apps;
    use App\Controller\Api\Api;
    use App\Model\Entity\Apps\UsersEntity;
    use App\DatabaseManager\Database;

    class ContactsApiController extends Api {
        
        //====================================================================================
        // Método responsável por retornar a lista de utilizadores cadastrados no sistema
        //====================================================================================
        public static function getAppContacts($request) {
            $db = new Database();
            $query = "SELECT u.id, u.name, u.surname, u.phone_number, up.profile_photo, up.profile_key 
                      FROM users u 
                      LEFT JOIN user_profiles up ON u.id = up.user_id 
                      WHERE u.deleted_at IS NULL 
                      ORDER BY u.id ASC";
            $results = $db->execute($query);
            
            $contacts = [];
            while ($obUser = $results->fetchObject()) {
                $contacts[] = [
                    'id'            => (int)$obUser->id,
                    'name'          => !empty($obUser->name) ? $obUser->name : $obUser->phone_number,
                    'surname'       => $obUser->surname ?? '',
                    'phone_number'  => $obUser->phone_number,
                    'profile_photo' => self::getProfilePhotoUrl($obUser->profile_photo),
                    'public_key'    => $obUser->profile_key
                ];
            }

            return [
                'success' => true,
                'contacts' => $contacts
            ];
        }

        private static function getProfilePhotoUrl($photo) {
            if (empty($photo)) {
                return null;
            }
            return getenv('URL') . '/images/avatars/' . $photo;
        }
    }
?>
