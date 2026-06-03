<?php
require __DIR__ . '/vendor/autoload.php';

use App\DatabaseManager\Database;
use App\Model\Entity\Apps\UserKeysEntity;
use App\Model\Entity\Apps\UserPrekeysEntity;

Database::config('127.0.0.1', 'massoko_database', 'root', 'root', '3306');

$userKey = UserKeysEntity::getUserKeyByUserId(1);
echo "UserKey:\n";
print_r($userKey);

$prekey = UserPrekeysEntity::getAvailablePrekey(1);
echo "\nPrekey:\n";
print_r($prekey);
