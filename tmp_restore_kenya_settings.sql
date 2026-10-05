INSERT INTO multi_currencies (currency_name,country_code,currency_code,currency_icon,is_default,currency_rate,currency_position,status,created_at,updated_at)
SELECT 'KSh-Kenyan Shilling','KE','KES','KSh','no',129.0,'before_price','active',NOW(),NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM multi_currencies WHERE currency_code='KES');

UPDATE multi_currencies SET is_default='no';

UPDATE multi_currencies
SET currency_name='KSh-Kenyan Shilling',
    country_code='KE',
    currency_icon='KSh',
    is_default='yes',
    currency_rate=129.0,
    currency_position='before_price',
    status='active',
    updated_at=NOW()
WHERE currency_code='KES';

INSERT INTO settings (`key`,`value`,created_at,updated_at)
VALUES ('app_name','RevisionHubKenya',NOW(),NOW())
ON DUPLICATE KEY UPDATE `value`='RevisionHubKenya', updated_at=NOW();

UPDATE basic_payments
SET `value`=(SELECT id FROM (SELECT id FROM multi_currencies WHERE currency_code='KES' LIMIT 1) x),
    updated_at=NOW()
WHERE `key`='paypal_currency_id';

SELECT `key`,`value` FROM settings WHERE `key` IN ('app_name','logo','favicon');
SELECT id,currency_name,currency_code,currency_icon,is_default,currency_rate FROM multi_currencies ORDER BY id;
SELECT `key`,`value` FROM basic_payments WHERE `key`='paypal_currency_id';
