<?php
namespace App\Model\Entity\Apps;

use App\DatabaseManager\Database;

class MessageAttachmentsEntity
{
    public $id;
    public $message_id;
    public $file_name;
    public $mime_type;
    public $file_size;
    public $encrypted_file_key;
    public $encrypted_file_url;
    public $thumbnail_url;
    public $duration_seconds;
    public $width;
    public $height;
    public $created_at;

    public function cadastrar() {
        $this->id = (new Database('message_attachments'))->insert([
            'message_id'         => $this->message_id,
            'file_name'          => $this->file_name,
            'mime_type'          => $this->mime_type,
            'file_size'          => $this->file_size,
            'encrypted_file_key' => $this->encrypted_file_key,
            'encrypted_file_url' => $this->encrypted_file_url,
            'thumbnail_url'      => $this->thumbnail_url,
            'duration_seconds'   => $this->duration_seconds,
            'width'              => $this->width,
            'height'             => $this->height,
            'created_at'         => date('Y-m-d H:i:s')
        ]);
        return $this->id;
    }

    public static function getAttachments($where = null, $order = null, $limit = null, $fields = "*") {
        return (new Database('message_attachments'))->select($where, $order, $limit, $fields);
    }

    public static function getAttachmentsByMessageId($messageId) {
        return self::getAttachments('message_id = ' . (int)$messageId);
    }
}
