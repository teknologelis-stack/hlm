USE mikrotik_panel;

-- Update version to 1.0.3
UPDATE settings SET setting_value = '1.0.3' WHERE setting_key = 'app_version';

SELECT 'Version updated to 1.0.3' as info;
SELECT * FROM settings WHERE setting_key = 'app_version';
