UPDATE settings SET `value`='RevisionHubKenya', updated_at=NOW() WHERE `key`='app_name';
DELETE s1 FROM settings s1
INNER JOIN settings s2
  ON s1.`key` = s2.`key`
 AND s1.id < s2.id
WHERE s1.`key`='app_name';
SELECT id,`key`,`value` FROM settings WHERE `key`='app_name';
SELECT id,currency_name,currency_code,currency_icon,is_default,currency_rate FROM multi_currencies WHERE currency_code='KES' OR is_default='yes' ORDER BY id;
