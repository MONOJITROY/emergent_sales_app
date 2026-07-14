<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

final class Transaction extends Model
{
    protected static string $table = 'transactions';

    public static function nextReceiptNo(): string
    {
        $row = self::db()->query("SELECT transaction_no FROM transactions WHERE type='receipt' ORDER BY id DESC LIMIT 1")->fetch();
        if (!$row) return 'REC-00001';
        $num = (int)substr($row['transaction_no'], 4) + 1;
        return 'REC-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public static function nextPaymentNo(): string
    {
        $row = self::db()->query("SELECT transaction_no FROM transactions WHERE type='payment' ORDER BY id DESC LIMIT 1")->fetch();
        if (!$row) return 'PAY-00001';
        $num = (int)substr($row['transaction_no'], 4) + 1;
        return 'PAY-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public static function listAll(string $type, string $q = ''): array
    {
        $sql = "SELECT t.* FROM transactions t WHERE t.type = ?";
        $params = [$type];
        if ($q !== '') {
            $sql .= " AND (t.transaction_no LIKE ? OR t.party_name LIKE ? OR t.reference_no LIKE ?)";
            $like = "%{$q}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY t.id DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function withAllocations(int $id): ?array
    {
        $stmt = self::db()->prepare("SELECT t.* FROM transactions t WHERE t.id = ?");
        $stmt->execute([$id]);
        $tx = $stmt->fetch();
        if (!$tx) return null;

        try {
            $alloc = self::db()->prepare(
                "SELECT ta.*, p.supplier_inv_no
                 FROM transaction_allocations ta
                 LEFT JOIN purchases p ON ta.invoice_type = 'purchase' AND ta.invoice_id = p.id
                 WHERE ta.transaction_id = ?"
            );
            $alloc->execute([$id]);
            $tx['allocations'] = $alloc->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $alloc = self::db()->prepare("SELECT * FROM transaction_allocations WHERE transaction_id = ?");
            $alloc->execute([$id]);
            $tx['allocations'] = $alloc->fetchAll() ?: [];
        }

        return $tx;
    }

    public static function pendingList(string $type): array
    {
        $stmt = self::db()->prepare("SELECT * FROM transactions WHERE type = ? AND reconciliation_status = 'pending' ORDER BY transaction_date ASC, id ASC");
        $stmt->execute([$type]);
        $rows = $stmt->fetchAll();

        if (!$rows) return [];

        $ids = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $allocStmt = self::db()->prepare(
                "SELECT ta.*, p.supplier_inv_no
                 FROM transaction_allocations ta
                 LEFT JOIN purchases p ON ta.invoice_type = 'purchase' AND ta.invoice_id = p.id
                 WHERE ta.transaction_id IN ({$placeholders})"
            );
            $allocStmt->execute($ids);
            $allAllocs = $allocStmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $allAllocs = [];
        }

        $allocMap = [];
        foreach ($allAllocs as $a) {
            $allocMap[$a['transaction_id']][] = $a;
        }

        foreach ($rows as &$r) {
            $r['allocations'] = $allocMap[$r['id']] ?? [];
        }

        return $rows;
    }

    public static function settle(int $id, int $userId): bool
    {
        $stmt = self::db()->prepare("UPDATE transactions SET reconciliation_status = 'reconciled', reconciled_at = NOW(), reconciled_by = ? WHERE id = ? AND reconciliation_status = 'pending'");
        $stmt->execute([$userId, $id]);
        return $stmt->rowCount() > 0;
    }
}
