<?php namespace App\Models; use App\Core\Model;
final class Sale extends Model { protected static string $table = 'sales';
    public static function withItems(int $id): ?array {
        $row = self::find($id); if (!$row) return null;
        $c = self::db()->prepare('SELECT * FROM customers WHERE id = ?'); $c->execute([$row['customer_id']]);
        $s = self::db()->prepare('SELECT si.*, p.hsn, p.unit, tt.percentage AS tax_pct, tt.typeofduty FROM sale_items si LEFT JOIN products p ON si.product_id = p.id LEFT JOIN taxtypes tt ON p.taxtype_id = tt.id WHERE si.sale_id = ?'); $s->execute([$id]);
        $row['customer'] = $c->fetch();
        $row['items'] = $s->fetchAll();
        return $row;
    }
    public static function listAll(string $q = ''): array {
        $cols = 's.*, COALESCE(s.paid,0) AS paid, COALESCE(s.balance, s.total) AS balance, COALESCE(s.status,"unpaid") AS status';
        if ($q === '') {
            return self::db()->query("SELECT {$cols}, (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) AS item_count FROM sales s ORDER BY id DESC")->fetchAll();
        }
        $st = self::db()->prepare("SELECT {$cols}, (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) AS item_count FROM sales s WHERE invoice_no LIKE ? OR customer_name LIKE ? ORDER BY id DESC");
        $st->execute(["%$q%", "%$q%"]); return $st->fetchAll();
    }
    public static function nextInvoiceNo(): string {
        $row = self::db()->query("SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_no,5) AS UNSIGNED)),0)+1 AS n FROM sales WHERE invoice_no LIKE 'INV-%'")->fetch();
        return sprintf('INV-%05d', $row['n'] ?? 1);
    }
}
