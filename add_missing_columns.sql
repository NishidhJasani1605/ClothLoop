-- Add missing columns to users table
ALTER TABLE users
ADD COLUMN bio TEXT NULL,
ADD COLUMN profile_picture VARCHAR(255) NULL; 