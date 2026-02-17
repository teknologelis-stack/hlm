USE mikrotik_panel;

-- Update version to 1.0.2
UPDATE settings SET setting_value = '1.0.2' WHERE setting_key = 'app_version';

SELECT 'Version updated to 1.0.2' as info;
SELECT * FROM settings WHERE setting_key = 'app_version';
