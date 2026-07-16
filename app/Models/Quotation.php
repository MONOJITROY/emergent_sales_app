<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

final class Quotation extends Model
{
    protected static string $table = 'quotations';

    public static function withItems(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT q.*, c.address AS customer_address, c.phone AS customer_phone, c.email AS customer_email '
            . 'FROM quotations q '
            . 'LEFT JOIN customers c ON q.customer_id = c.id '
            . 'WHERE q.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $items = self::db()->prepare(
            'SELECT qi.*, p.hsn, p.unit, tt.percentage AS tax_pct, tt.typeofduty '
            . 'FROM quotation_items qi '
            . 'LEFT JOIN products p ON qi.product_id = p.id '
            . 'LEFT JOIN taxtypes tt ON p.taxtype_id = tt.id '
            . 'WHERE qi.quotation_id = ?'
        );
        $items->execute([$id]);
        $row['items'] = $items->fetchAll();
        return $row;
    }

    public static function nextQuoteNo(): string
    {
        $row = self::db()->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(quote_no, 5) AS UNSIGNED)), 0) + 1 AS n FROM quotations WHERE quote_no LIKE 'QUO-%'"
        )->fetch();
        return sprintf('QUO-%05d', $row['n'] ?? 1);
    }

    public static function listAll(string $q = ''): array
    {
        $cols = 'q.*, (SELECT COUNT(*) FROM quotation_items WHERE quotation_id = q.id) AS item_count';
        if ($q === '') {
            return self::db()->query("SELECT {$cols} FROM quotations q ORDER BY id DESC")->fetchAll();
        }
        $st = self::db()->prepare("SELECT {$cols} FROM quotations q WHERE quote_no LIKE ? OR customer_name LIKE ? ORDER BY id DESC");
        $st->execute(["%$q%", "%$q%"]);
        return $st->fetchAll();
    }
}
