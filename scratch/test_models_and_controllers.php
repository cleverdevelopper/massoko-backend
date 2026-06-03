<?php
require __DIR__ . '/../vendor/autoload.php';

use App\DatabaseManager\Database;
use App\Model\Entity\Apps\UsersEntity;
use App\Model\Entity\Apps\UserProfilesEntity;
use App\Controller\Api\V1\Apps\AuthenticationApiController;
use App\Controller\Api\V1\Apps\ContactsApiController;
use App\Controller\Api\V1\Apps\ConversationsApiController;
use App\Controller\Api\V1\Apps\GroupSenderKeysApiController;
use App\Controller\Api\V1\Apps\MessageActionsApiController;

// Config database from env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$key, $val] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}
Database::config(
    getenv('BD_HOST') ?: '127.0.0.1',
    getenv('BD_DATABASE') ?: 'massoko_database',
    getenv('BD_USERNAME') ?: 'root',
    getenv('BD_PASSWORD') ?: 'root',
    getenv('DB_PORT') ?: '3306'
);

class MockRequest {
    public $user;
    private $postVars = [];
    private $queryParams = [];

    public function __construct($postVars = [], $queryParams = []) {
        $this->postVars = $postVars;
        $this->queryParams = $queryParams;
    }

    public function getPostVars() {
        return $this->postVars;
    }

    public function getQueryParams() {
        return $this->queryParams;
    }
}

// ----------------------------------------------------
// Setup test data
// ----------------------------------------------------
echo "Setting up test data...\n";
$db = new Database();

// Ensure test users exist or create them
$db->execute("SET FOREIGN_KEY_CHECKS=0");
$db->execute("DELETE FROM users WHERE phone_number IN ('+258840000001', '+258840000002')");
$db->execute("INSERT INTO users (phone_number, name, surname) VALUES ('+258840000001', 'Test User', 'One')");
$db->execute("INSERT INTO users (phone_number, name, surname) VALUES ('+258840000002', 'Test User', 'Two')");
$db->execute("SET FOREIGN_KEY_CHECKS=1");

$user1 = UsersEntity::getUsers("phone_number = '+258840000001'")->fetchObject(UsersEntity::class);
$user2 = UsersEntity::getUsers("phone_number = '+258840000002'")->fetchObject(UsersEntity::class);

echo "Test User 1 ID: " . $user1->id . "\n";
echo "Test User 2 ID: " . $user2->id . "\n";

// Create profile for user 1
$db->execute("DELETE FROM user_profiles WHERE user_id IN ({$user1->id}, {$user2->id})");

// ----------------------------------------------------
// TEST 1: Profile Finalize Registration
// ----------------------------------------------------
echo "\n--- TEST 1: Finalize Registration ---\n";
$req = new MockRequest([
    'account_id' => $user1->id,
    'name' => 'Test User Updated',
    'surname' => 'One Updated',
    'public_key' => 'identity-key-123-abc',
    'avatar' => 'avatar1.png'
]);
$res = AuthenticationApiController::finalizeRegistration($req);
print_r($res);

// ----------------------------------------------------
// TEST 2: Get Profile
// ----------------------------------------------------
echo "\n--- TEST 2: Get User Profile ---\n";
$reqGetProfile = new MockRequest([], ['user_id' => $user1->id]);
$reqGetProfile->user = $user1;
$resGetProfile = AuthenticationApiController::getUserProfile($reqGetProfile);
print_r($resGetProfile);

// ----------------------------------------------------
// TEST 3: Get Contacts (Join Users and UserProfiles)
// ----------------------------------------------------
echo "\n--- TEST 3: Get Contacts ---\n";
$reqContacts = new MockRequest();
$reqContacts->user = $user1;
$resContacts = ContactsApiController::getAppContacts($reqContacts);
print_r($resContacts);

// Create conversation 9999 first
$db->execute("DELETE FROM conversation_participants WHERE conversation_id = 9999");
$db->execute("DELETE FROM conversations WHERE id = 9999");
$db->execute("INSERT INTO conversations (id, type) VALUES (9999, 'private')");
$db->execute("INSERT INTO conversation_participants (conversation_id, user_id, role) VALUES (9999, {$user1->id}, 'owner')");

// ----------------------------------------------------
// TEST 4: Group Sender Keys
// ----------------------------------------------------
echo "\n--- TEST 4: Group Sender Keys ---\n";

// Insert a fake user device for user 1
$db->execute("DELETE FROM user_devices WHERE user_id = {$user1->id}");
$db->execute("INSERT INTO user_devices (user_id, device_id, platform, registration_id, identity_key, signed_prekey_id, signed_prekey, signed_prekey_signature) VALUES ({$user1->id}, 1, 'android', 456, 'id-key-test', 1, 'signed-prekey-test', 'signature-test')");
$device = $db->execute("SELECT id FROM user_devices WHERE user_id = {$user1->id} LIMIT 1")->fetchObject();

$reqSaveKey = new MockRequest([
    'conversation_id' => 9999,
    'user_device_id' => $device->id,
    'sender_key' => 'sender-key-e2e-data-12345'
]);
$reqSaveKey->user = $user1;
$resSaveKey = GroupSenderKeysApiController::saveSenderKey($reqSaveKey);
print_r($resSaveKey);

$reqGetKey = new MockRequest([], [
    'conversation_id' => 9999
]);
$reqGetKey->user = $user1;
$resGetKey = GroupSenderKeysApiController::getSenderKeys($reqGetKey);
print_r($resGetKey);

// ----------------------------------------------------
// TEST 5: Message Actions (Reactions, Edits, Deletions, Attachments)
// ----------------------------------------------------
echo "\n--- TEST 5: Message Actions ---\n";

$db->execute("DELETE FROM messages WHERE conversation_id = 9999");
$db->execute("INSERT INTO messages (conversation_id, sender_id, encrypted_content, signal_message_type) VALUES (9999, {$user1->id}, 'hello encrypted message', 1)");
$msg = $db->execute("SELECT id FROM messages WHERE conversation_id = 9999 LIMIT 1")->fetchObject();
echo "Inserted Message ID: " . $msg->id . "\n";

// Test Reaction
echo "Adding reaction...\n";
$reqReact = new MockRequest(['reaction' => '❤️']);
$reqReact->user = $user1;
$resReact = MessageActionsApiController::addReaction($reqReact, $msg->id);
print_r($resReact);

echo "Getting reactions...\n";
$resGetReacts = MessageActionsApiController::getReactions($reqReact, $msg->id);
print_r($resGetReacts);

// Test Edit
echo "Editing message...\n";
$reqEdit = new MockRequest(['content' => 'hello updated encrypted message']);
$reqEdit->user = $user1;
$resEdit = MessageActionsApiController::editMessage($reqEdit, $msg->id);
print_r($resEdit);

echo "Getting edit history...\n";
$resHistory = MessageActionsApiController::getEditHistory($reqEdit, $msg->id);
print_r($resHistory);

// Test Attachments
echo "Adding attachment...\n";
$reqAttach = new MockRequest([
    'message_id' => $msg->id,
    'file_name' => 'photo.jpg',
    'mime_type' => 'image/jpeg',
    'file_size' => 1024,
    'encrypted_file_key' => 'file-key-enc',
    'encrypted_file_url' => 'https://example.com/file.enc'
]);
$reqAttach->user = $user1;
$resAttach = MessageActionsApiController::addAttachment($reqAttach);
print_r($resAttach);

echo "Getting attachments...\n";
$resGetAttach = MessageActionsApiController::getAttachments($reqAttach, $msg->id);
print_r($resGetAttach);

// Test Delete for everyone
echo "Deleting message for everyone...\n";
$reqDelete = new MockRequest();
$reqDelete->user = $user1;
$resDelete = MessageActionsApiController::deleteMessageForEveryone($reqDelete, $msg->id);
print_r($resDelete);

// Clean up
echo "\nCleaning up database...\n";
$db->execute("SET FOREIGN_KEY_CHECKS=0");
$db->execute("DELETE FROM message_attachments WHERE message_id = {$msg->id}");
$db->execute("DELETE FROM message_reactions WHERE message_id = {$msg->id}");
$db->execute("DELETE FROM message_edits WHERE message_id = {$msg->id}");
$db->execute("DELETE FROM deleted_messages WHERE message_id = {$msg->id}");
$db->execute("DELETE FROM messages WHERE conversation_id = 9999");
$db->execute("DELETE FROM conversation_participants WHERE conversation_id = 9999");
$db->execute("DELETE FROM conversations WHERE id = 9999");
$db->execute("DELETE FROM group_sender_keys WHERE conversation_id = 9999");
$db->execute("DELETE FROM user_devices WHERE user_id = {$user1->id}");
$db->execute("DELETE FROM user_profiles WHERE user_id = {$user1->id}");
$db->execute("DELETE FROM users WHERE id IN ({$user1->id}, {$user2->id})");
$db->execute("SET FOREIGN_KEY_CHECKS=1");

echo "\nAll Tests Completed!\n";
