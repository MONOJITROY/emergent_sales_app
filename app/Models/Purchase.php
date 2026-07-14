<?php namespace App\Models; use App\Core\Model; use App\Core\Database;
final class Purchase extends Model { protected static string $table = 'purchases';
    public static function listAll(string $q = ''): array {
        $cols = 'p.*, COALESCE(p.paid,0) AS paid, COALESCE(p.balance, p.total) AS balance, COALESCE(p.status,"unpaid") AS status';
        if ($q === '') return self::db()->query("SELECT {$cols}, (SELECT COUNT(*) FROM purchase_items WHERE purchase_id = p.id) AS item_count FROM purchases p ORDER BY id DESC")->fetchAll();
        $st = self::db()->prepare("SELECT {$cols}, (SELECT COUNT(*) FROM purchase_items WHERE purchase_id = p.id) AS item_count FROM purchases p WHERE ref_no LIKE ? OR supplier_name LIKE ? ORDER BY id DESC");
        $st->execute(["%$q%", "%$q%"]); return $st->fetchAll();
    }
    public static function nextRefNo(): string {
        $row = self::db()->query("SELECT COALESCE(MAX(CAST(SUBSTRING(ref_no,5) AS UNSIGNED)),0)+1 AS n FROM purchases WHERE ref_no LIKE 'PUR-%'")->fetch();
        return sprintf('PUR-%05d', $row['n'] ?? 1);
    }
    public static function items(int $id): array {
        $s = self::db()->prepare('SELECT * FROM purchase_items WHERE purchase_id = ?'); $s->execute([$id]);
        return $s->fetchAll();
    }
    public static function withItems(int $id): ?array {
        $stmt = self::db()->prepare('SELECT p.*, s.email AS supplier_email, s.phone AS supplier_phone, s.address AS supplier_address FROM purchases p LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE p.id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) return null;
        $p['items'] = self::items($id);
        return $p;
    }
    public static function pendingForParty(string $partyType, int $partyId): array {
        $table = $partyType === 'customer' ? 'sales' : 'purchases';
        $partyCol = $partyType === 'customer' ? 'customer_id' : 'supplier_id';
        $stmt = self::db()->prepare("SELECT *, COALESCE(paid,0) AS paid, COALESCE(balance, total) AS balance, COALESCE(status,'unpaid') AS status FROM {$table} WHERE {$partyCol} = ? AND COALESCE(balance, total) > 0 ORDER BY id ASC");
        $stmt->execute([$partyId]);
        return $stmt->fetchAll();
    }
}