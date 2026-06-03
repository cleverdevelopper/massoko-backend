<?php
require __DIR__ . '/vendor/autoload.php';

use App\DatabaseManager\Database;
use App\Controller\Api\V1\Apps\KeysApiController;

Database::config('127.0.0.1', 'massoko_database', 'root', 'root', '3306');

// Mock request
class MockRequest {
    public $user;
    public function getParams() {
        return ['id' => 1];
    }
}

$request = new MockRequest();
$request->user = (object)['id' => 2]; // I am user 2, requesting keys for user 1

$response = KeysApiController::getKeyBundle($request);

echo "Response Class: " . get_class($response) . "\n";
echo "Response Output:\n";
$response->sendResponse();
