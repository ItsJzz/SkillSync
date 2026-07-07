-- Add theme_preference and email_notifications columns to login_credentials table if they don't exist

-- Check if columns exist and add them if needed
ALTER TABLE login_credentials 
ADD COLUMN IF NOT EXISTS theme_preference VARCHAR(10) DEFAULT 'light',
ADD COLUMN IF NOT EXISTS email_notifications TINYINT(1) DEFAULT 1;

-- Update existing records to have default values
UPDATE login_credentials 
SET theme_preference = 'light' 
WHERE theme_preference IS NULL;

UPDATE login_credentials 
SET email_notifications = 1 
WHERE email_notifications IS NULL;
