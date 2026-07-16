<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

final class CreditNote extends Model
{
    protected static string $table = 'credit_notes';

    public static function withItems(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT cn.*, c.address AS customer_address, c.phone AS customer_phone, c.email AS customer_email, s.invoice_no AS sale_invoice_no, s.sale_date AS sale_date '
            . 'FROM credit_notes cn '
            . 'LEFT JOIN customers c ON cn.customer_id = c.id '
            . 'LEFT JOIN sales s ON cn.sale_id = s.id '
            . 'WHERE cn.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $items = self::db()->prepare(
            'SELECT ci.*, p.hsn, p.unit, tt.percentage AS tax_pct, tt.typeofduty '
            . 'FROM credit_note_items ci '
            . 'LEFT JOIN products p ON ci.product_id = p.id '
            . 'LEFT JOIN taxtypes tt ON p.taxtype_id = tt.id '
            . 'WHERE ci.credit_note_id = ?'
        );
        $items->execute([$id]);
        $row['items'] = $items->fetchAll();
        return $row;
    }

    public static function nextCnNo(): string
    {
        $row = self::db()->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(cn_no, 4) AS UNSIGNED)), 0) + 1 AS n FROM credit_notes WHERE cn_no LIKE 'CN-%'"
        )->fetch();
        return sprintf('CN-%05d', $row['n'] ?? 1);
    }

    public static function listAll(string $q = ''): array
    {
        $cols = 'cn.*, (SELECT COUNT(*) FROM credit_note_items WHERE credit_note_id = cn.id) AS item_count';
        if ($q === '') {
            return self::db()->query("SELECT {$cols} FROM credit_notes cn ORDER BY id DESC")->fetchAll();
        }
        $st = self::db()->prepare("SELECT {$cols} FROM credit_notes cn WHERE cn_no LIKE ? OR customer_name LIKE ? ORDER BY id DESC");
        $st->execute(["%$q%", "%$q%"]);
        return $st->fetchAll();
    }
}
