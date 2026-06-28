<?php namespace App\Models; use App\Core\Model;
final class Purchase extends Model { protected static string $table = 'purchases';
    public static function listAll(string $q = ''): array {
        if ($q === '') return self::db()->query('SELECT p.*, (SELECT COUNT(*) FROM purchase_items WHERE purchase_id = p.id) AS item_count FROM purchases p ORDER BY id DESC')->fetchAll();
        $st = self::db()->prepare('SELECT p.*, (SELECT COUNT(*) FROM purchase_items WHERE purchase_id = p.id) AS item_count FROM purchases p WHERE ref_no LIKE ? OR supplier_name LIKE ? ORDER BY id DESC');
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
}
