-- Switch M-Pesa STK Push from sandbox to production
-- Run this on the live database.

UPDATE basic_payments
SET value = 'production',
    updated_at = NOW()
WHERE `key` = 'mpesa_stk_push_account_mode';

-- Optional sanity check
SELECT `key`, value
FROM basic_payments
WHERE `key` IN ('mpesa_stk_push_account_mode', 'mpesa_stk_push_status');
