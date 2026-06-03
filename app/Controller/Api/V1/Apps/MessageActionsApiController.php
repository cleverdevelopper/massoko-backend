<?php
namespace App\Controller\Api\V1\Apps;

use App\Controller\Api\Api;
use App\Model\Entity\Apps\MessagesEntity;
use App\Model\Entity\Apps\MessageAttachmentsEntity;
use App\Model\Entity\Apps\MessageReactionsEntity;
use App\Model\Entity\Apps\MessageEditsEntity;
use App\Model\Entity\Apps\DeletedMessagesEntity;
use App\DatabaseManager\Database;
use Exception;

class MessageActionsApiController extends Api {

    // ============================================================
    // ATTACHMENTS
    // ============================================================

    public static function addAttachment($request) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        $postVars = $request->getPostVars();
        $messageId         = $postVars['message_id'] ?? null;
        $fileName          = $postVars['file_name'] ?? null;
        $mimeType          = $postVars['mime_type'] ?? null;
        $fileSize          = $postVars['file_size'] ?? null;
        $encryptedFileKey  = $postVars['encrypted_file_key'] ?? null;
        $encryptedFileUrl  = $postVars['encrypted_file_url'] ?? null;
        $thumbnailUrl      = $postVars['thumbnail_url'] ?? null;
        $durationSeconds   = $postVars['duration_seconds'] ?? null;
        $width             = $postVars['width'] ?? null;
        $height            = $postVars['height'] ?? null;

        if (!$messageId || !$encryptedFileUrl) {
            return self::error('message_id e encrypted_file_url são obrigatórios', 400);
        }

        $message = MessagesEntity::getMessageById($messageId);
        if (!$message) return self::error('Mensagem não encontrada', 404);

        $attachment = new MessageAttachmentsEntity();
        $attachment->message_id         = $messageId;
        $attachment->file_name          = $fileName;
        $attachment->mime_type          = $mimeType;
        $attachment->file_size          = $fileSize;
        $attachment->encrypted_file_key = $encryptedFileKey;
        $attachment->encrypted_file_url = $encryptedFileUrl;
        $attachment->thumbnail_url      = $thumbnailUrl;
        $attachment->duration_seconds   = $durationSeconds;
        $attachment->width              = $width;
        $attachment->height             = $height;
        $id = $attachment->cadastrar();

        return self::success('Anexo adicionado com sucesso', ['id' => (int)$id], 201);
    }

    public static function getAttachments($request, $messageId) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        if (!$messageId || !is_numeric($messageId)) {
            return self::error('ID da mensagem inválido', 400);
        }

        $results = MessageAttachmentsEntity::getAttachmentsByMessageId($messageId);
        $attachments = [];
        while ($att = $results->fetchObject(MessageAttachmentsEntity::class)) {
            $attachments[] = [
                'id'                 => (int)$att->id,
                'message_id'         => (int)$att->message_id,
                'file_name'          => $att->file_name,
                'mime_type'          => $att->mime_type,
                'file_size'          => $att->file_size ? (int)$att->file_size : null,
                'encrypted_file_key' => $att->encrypted_file_key,
                'encrypted_file_url' => $att->encrypted_file_url,
                'thumbnail_url'      => $att->thumbnail_url,
                'duration_seconds'   => $att->duration_seconds ? (int)$att->duration_seconds : null,
                'width'              => $att->width ? (int)$att->width : null,
                'height'             => $att->height ? (int)$att->height : null,
                'created_at'         => $att->created_at
            ];
        }

        return self::success('Anexos obtidos', ['attachments' => $attachments], 200);
    }

    // ============================================================
    // REACTIONS
    // ============================================================

    public static function addReaction($request, $messageId) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        $postVars = $request->getPostVars();
        $reaction = $postVars['reaction'] ?? null;

        if (!$messageId || !is_numeric($messageId) || !$reaction) {
            return self::error('Parâmetros inválidos', 400);
        }

        $obReaction = new MessageReactionsEntity();
        $obReaction->message_id = $messageId;
        $obReaction->user_id    = $loggedUser->id;
        $obReaction->reaction   = $reaction;
        $id = $obReaction->cadastrar();

        return self::success('Reação adicionada com sucesso', ['id' => (int)$id], 200);
    }

    public static function removeReaction($request, $messageId) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        if (!$messageId || !is_numeric($messageId)) {
            return self::error('ID de mensagem inválido', 400);
        }

        MessageReactionsEntity::deleteReaction($messageId, $loggedUser->id);

        return self::success('Reação removida', [], 200);
    }

    public static function getReactions($request, $messageId) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        if (!$messageId || !is_numeric($messageId)) {
            return self::error('ID de mensagem inválido', 400);
        }

        $results = MessageReactionsEntity::getReactionsByMessageId($messageId);
        $reactions = [];
        while ($react = $results->fetchObject(MessageReactionsEntity::class)) {
            $reactions[] = [
                'id'         => (int)$react->id,
                'message_id' => (int)$react->message_id,
                'user_id'    => (int)$react->user_id,
                'reaction'   => $react->reaction,
                'created_at' => $react->created_at
            ];
        }

        return self::success('Reações obtidas', ['reactions' => $reactions], 200);
    }

    // ============================================================
    // EDITS
    // ============================================================

    public static function editMessage($request, $messageId) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        $postVars = $request->getPostVars();
        $newContent = $postVars['content'] ?? null;

        if (!$messageId || !is_numeric($messageId) || !$newContent) {
            return self::error('Novos conteúdos e ID da mensagem são necessários', 400);
        }

        $message = MessagesEntity::getMessageById($messageId);
        if (!$message) return self::error('Mensagem não encontrada', 404);

        if ($message->sender_id != $loggedUser->id) {
            return self::error('Não tem permissão para editar esta mensagem', 403);
        }

        $db = new Database();
        try {
            $db->beginTransaction();

            // Guardar histórico de edições
            $edit = new MessageEditsEntity();
            $edit->message_id       = $messageId;
            $edit->previous_content = $message->encrypted_content;
            $edit->cadastrar();

            // Atualizar mensagem original
            $message->encrypted_content = $newContent;
            $message->edited = 1;
            $message->updated_at = date('Y-m-d H:i:s');
            $message->actualizar();

            $db->commit();
            return self::success('Mensagem editada com sucesso', [], 200);
        } catch (Exception $e) {
            $db->rollBack();
            return self::error('Erro ao editar mensagem: ' . $e->getMessage(), 500);
        }
    }

    public static function getEditHistory($request, $messageId) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        if (!$messageId || !is_numeric($messageId)) {
            return self::error('ID de mensagem inválido', 400);
        }

        $results = MessageEditsEntity::getEditsByMessageId($messageId);
        $history = [];
        while ($edit = $results->fetchObject(MessageEditsEntity::class)) {
            $history[] = [
                'id'               => (int)$edit->id,
                'message_id'       => (int)$edit->message_id,
                'previous_content' => $edit->previous_content,
                'edited_at'        => $edit->edited_at
            ];
        }

        return self::success('Histórico de edições obtido', ['history' => $history], 200);
    }

    // ============================================================
    // DELETE FOR EVERYONE
    // ============================================================

    public static function deleteMessageForEveryone($request, $messageId) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        if (!$messageId || !is_numeric($messageId)) {
            return self::error('ID de mensagem inválido', 400);
        }

        $message = MessagesEntity::getMessageById($messageId);
        if (!$message) return self::error('Mensagem não encontrada', 404);

        if ($message->sender_id != $loggedUser->id) {
            return self::error('Não tem permissão para apagar esta mensagem para todos', 403);
        }

        $db = new Database();
        try {
            $db->beginTransaction();

            // Registar em deleted_messages
            $del = new DeletedMessagesEntity();
            $del->message_id = $messageId;
            $del->deleted_by = $loggedUser->id;
            $del->cadastrar();

            // Limpar conteúdo e marcar como apagada na tabela de mensagens
            $message->encrypted_content = ''; // Limpa conteúdo encriptado
            $message->deleted_at = date('Y-m-d H:i:s');
            $message->updated_at = date('Y-m-d H:i:s');
            $message->actualizar();

            $db->commit();
            return self::success('Mensagem apagada para todos com sucesso', [], 200);
        } catch (Exception $e) {
            $db->rollBack();
            return self::error('Erro ao apagar mensagem: ' . $e->getMessage(), 500);
        }
    }
}
