-- Add Firebase UID column to users table
ALTER TABLE users
ADD COLUMN firebase_uid VARCHAR(128) NULL AFTER password,
ADD UNIQUE INDEX firebase_uid_idx (firebase_uid);

-- Make password field nullable for Firebase users
ALTER TABLE users
MODIFY COLUMN password VARCHAR(255) NULL;

-- Add last login timestamp
ALTER TABLE users
ADD COLUMN last_login DATETIME NULL AFTER created_at;

-- Add login provider field
ALTER TABLE users
ADD COLUMN login_provider ENUM('email', 'google', 'facebook', 'traditional') DEFAULT 'traditional' AFTER firebase_uid; 