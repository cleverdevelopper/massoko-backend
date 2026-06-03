-- Database migration script for Signal Protocol upgrade
-- Note: This script assumes you might need to drop some existing tables before applying the new ones.
-- Use with caution in production!

SET FOREIGN_KEY_CHECKS=0;

-- Drop existing old tables if they exist to avoid conflicts (adjust as needed for production)
DROP TABLE IF EXISTS group_sender_keys;
DROP TABLE IF EXISTS deleted_messages;
DROP TABLE IF EXISTS message_edits;
DROP TABLE IF EXISTS message_reactions;
DROP TABLE IF EXISTS pending_device_messages;
DROP TABLE IF EXISTS message_deliveries;
DROP TABLE IF EXISTS message_attachments;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversation_participants;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS device_prekeys;
DROP TABLE IF EXISTS signed_prekey_history;
DROP TABLE IF EXISTS device_sessions;
DROP TABLE IF EXISTS user_devices;
DROP TABLE IF EXISTS refresh_tokens;
DROP TABLE IF EXISTS otps;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;

-- =====================================================
-- USERS
-- =====================================================

CREATE TABLE users (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number                VARCHAR(20) UNIQUE NOT NULL,
    name                        VARCHAR(120) NULL,
    surname                     VARCHAR(120) NULL,
    is_online                   BOOLEAN DEFAULT FALSE,
    last_seen                   DATETIME NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                  DATETIME NULL
);

-- =====================================================
-- USER PROFILES
-- =====================================================

CREATE TABLE user_profiles (
    user_id                     BIGINT UNSIGNED PRIMARY KEY,
    profile_name                VARCHAR(255) NULL,
    profile_photo               VARCHAR(255) NULL,
    about                       VARCHAR(255) NULL,
    profile_key                 TEXT NULL,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- OTPS
-- =====================================================

CREATE TABLE otps (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     BIGINT UNSIGNED NOT NULL,
    code                        INT NOT NULL,
    expires_at                  DATETIME NOT NULL,
    verified                    BOOLEAN DEFAULT FALSE,
    created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- REFRESH TOKENS
-- =====================================================

CREATE TABLE refresh_tokens (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     BIGINT UNSIGNED NOT NULL,
    token                       VARCHAR(255) NOT NULL,
    expires_at                  DATETIME NOT NULL,
    revoked                     BOOLEAN DEFAULT FALSE,
    device_info                 VARCHAR(255) NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- DEVICES
-- =====================================================

CREATE TABLE user_devices (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     BIGINT UNSIGNED NOT NULL,
    device_id                   INT NOT NULL,
    platform                    ENUM('android', 'ios', 'web') NOT NULL,
    device_name                 VARCHAR(255) NULL,
    registration_id             INT NOT NULL,
    identity_key                TEXT NOT NULL,
    signed_prekey_id            INT NOT NULL,
    signed_prekey               TEXT NOT NULL,
    signed_prekey_signature     TEXT NOT NULL,
    current_signed_prekey_id    INT NULL,
    is_active                   BOOLEAN DEFAULT TRUE,
    last_seen                   DATETIME NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device (user_id, device_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- SIGNED PREKEY HISTORY
-- =====================================================

CREATE TABLE signed_prekey_history (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_device_id              BIGINT UNSIGNED NOT NULL,
    signed_prekey_id            INT NOT NULL,
    public_key                  TEXT NOT NULL,
    signature                   TEXT NOT NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);

-- =====================================================
-- ONE TIME PREKEYS
-- =====================================================

CREATE TABLE device_prekeys (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id                   BIGINT UNSIGNED NOT NULL,
    prekey_id                   INT NOT NULL,
    public_key                  TEXT NOT NULL,
    used                        BOOLEAN DEFAULT FALSE,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_prekey (device_id, prekey_id),

    FOREIGN KEY (device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);

-- =====================================================
-- DEVICE SESSIONS
-- =====================================================

CREATE TABLE device_sessions (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_device_id            BIGINT UNSIGNED NOT NULL,
    target_device_id            BIGINT UNSIGNED NOT NULL,
    session_data                LONGTEXT NOT NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session (source_device_id, target_device_id),

    FOREIGN KEY (source_device_id) REFERENCES user_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (target_device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);

-- =====================================================
-- LINKED DEVICES
-- =====================================================

CREATE TABLE linked_devices (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    primary_device_id           BIGINT UNSIGNED NOT NULL,
    secondary_device_id         BIGINT UNSIGNED NOT NULL,
    status                      ENUM('pending', 'active', 'revoked') DEFAULT 'pending',
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (primary_device_id) REFERENCES user_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (secondary_device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);


-- =====================================================
-- CONVERSATIONS
-- =====================================================

CREATE TABLE conversations (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type                        ENUM('private', 'group') DEFAULT 'private',
    title                       VARCHAR(255) NULL,
    avatar                      VARCHAR(255) NULL,
    created_by                  BIGINT UNSIGNED NULL,
    last_message_id             BIGINT UNSIGNED NULL,
    last_message_at             DATETIME NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                  DATETIME NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- PARTICIPANTS
-- =====================================================

CREATE TABLE conversation_participants (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id             BIGINT UNSIGNED NOT NULL,
    user_id                     BIGINT UNSIGNED NOT NULL,
    role                        ENUM('owner', 'admin', 'member') DEFAULT 'member',
    joined_at                   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_message_id        BIGINT UNSIGNED NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                  DATETIME NULL,
    UNIQUE KEY unique_member (conversation_id, user_id),

    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- GROUP SENDER KEYS
-- =====================================================

CREATE TABLE group_sender_keys (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id             BIGINT UNSIGNED NOT NULL,
    user_device_id              BIGINT UNSIGNED NOT NULL,
    sender_key                  LONGTEXT NOT NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);

-- =====================================================
-- MESSAGES
-- =====================================================

CREATE TABLE messages (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id             BIGINT UNSIGNED NOT NULL,
    sender_id                   BIGINT UNSIGNED NOT NULL,
    encrypted_content           LONGTEXT NOT NULL,
    signal_message_type         TINYINT NOT NULL,
    message_type                ENUM('text', 'image', 'video', 'audio', 'document', 'location', 'contact', 'system') DEFAULT 'text',
    reply_to_message_id         BIGINT UNSIGNED NULL,
    edited                      BOOLEAN DEFAULT FALSE,
    sent_at                     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                  DATETIME NULL,
    INDEX idx_conversation_time (conversation_id, sent_at),

    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_message_id) REFERENCES messages(id) ON DELETE SET NULL
);

-- =====================================================
-- ATTACHMENTS
-- =====================================================

CREATE TABLE message_attachments (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id                  BIGINT UNSIGNED NOT NULL,
    file_name                   VARCHAR(255) NULL,
    mime_type                   VARCHAR(150) NULL,
    file_size                   BIGINT NULL,
    encrypted_file_key          TEXT NULL,
    encrypted_file_url          VARCHAR(255) NOT NULL,
    thumbnail_url               VARCHAR(255) NULL,
    duration_seconds            INT NULL,
    width                       INT NULL,
    height                      INT NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);

-- =====================================================
-- DELIVERIES
-- =====================================================

CREATE TABLE message_deliveries (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id                  BIGINT UNSIGNED NOT NULL,
    user_device_id              BIGINT UNSIGNED NOT NULL,
    delivered_at                DATETIME NULL,
    read_at                     DATETIME NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_delivery (message_id, user_device_id),

    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);

-- =====================================================
-- OFFLINE QUEUE
-- =====================================================

CREATE TABLE pending_device_messages (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id                  BIGINT UNSIGNED NOT NULL,
    user_device_id              BIGINT UNSIGNED NOT NULL,
    delivered                   BOOLEAN DEFAULT FALSE,
    delivered_at                DATETIME NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);

-- =====================================================
-- REACTIONS
-- =====================================================

CREATE TABLE message_reactions (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id                  BIGINT UNSIGNED NOT NULL,
    user_id                     BIGINT UNSIGNED NOT NULL,
    reaction                    VARCHAR(20) NOT NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (message_id, user_id),

    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- MESSAGE EDIT HISTORY
-- =====================================================

CREATE TABLE message_edits (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id                  BIGINT UNSIGNED NOT NULL,
    previous_content            LONGTEXT NOT NULL,
    edited_at                   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);

-- =====================================================
-- DELETE FOR EVERYONE
-- =====================================================

CREATE TABLE deleted_messages (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id                  BIGINT UNSIGNED NOT NULL,
    deleted_by                  BIGINT UNSIGNED NOT NULL,
    deleted_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS=1;
