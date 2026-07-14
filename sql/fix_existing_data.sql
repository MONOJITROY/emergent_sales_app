-- Fix: backfill balance/status for pre-existing purchases and sales
-- Run this once to correct stale zero-balance records

-- 1. Purchases: set balance = total where paid is 0/null
UPDATE purchases SET balance = total, status = 'unpaid' WHERE (paid = 0 OR paid IS NULL) AND total > 0;

-- 2. Sales: set balance = total - paid where balance is 0/null but total > paid
UPDATE sales SET balance = total - COALESCE(paid, 0) WHERE (balance IS NULL OR balance = 0) AND total > COALESCE(paid, 0);
UPDATE sales SET status = 'unpaid' WHERE paid = 0 AND total > 0;
UPDATE sales SET status = 'partial' WHERE paid > 0 AND paid < total;
UPDATE sales SET status = 'paid' WHERE paid >= total;

-- 3. Recompute supplier balances from purchases
UPDATE suppliers s SET balance = (
    SELECT COALESCE(SUM(COALESCE(balance, total)), 0) FROM purchases p WHERE p.supplier_id = s.id
);

-- 4. Recompute customer balances from sales
UPDATE customers c SET balance = (
    SELECT COALESCE(SUM(COALESCE(balance, total - COALESCE(paid, 0))), 0) FROM sales s WHERE s.customer_id = c.id
);

-- 5. Drop broken FK on transactions.party_id (references customers, but payments reference suppliers)
SET @fk_name = (
    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME LIKE '%ibfk%'
    LIMIT 1
);
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE transactions DROP FOREIGN KEY `', @fk_name, '`'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
