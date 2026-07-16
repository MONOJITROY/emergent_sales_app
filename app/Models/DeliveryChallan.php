<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

final class DeliveryChallan extends Model
{
    protected static string $table = 'delivery_challans';

    public static function withItems(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT dc.*, c.address AS customer_address, c.phone AS customer_phone, c.email AS customer_email, s.invoice_no AS sale_invoice_no '
            . 'FROM delivery_challans dc '
            . 'LEFT JOIN customers c ON dc.customer_id = c.id '
            . 'LEFT JOIN sales s ON dc.sale_id = s.id '
            . 'WHERE dc.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $items = self::db()->prepare(
            'SELECT di.*, p.hsn, p.unit '
            . 'FROM delivery_challan_items di '
            . 'LEFT JOIN products p ON di.product_id = p.id '
            . 'WHERE di.challan_id = ?'
        );
        $items->execute([$id]);
        $row['items'] = $items->fetchAll();
        return $row;
    }

    public static function nextDcNo(): string
    {
        $row = self::db()->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(dc_no, 4) AS UNSIGNED)), 0) + 1 AS n FROM delivery_challans WHERE dc_no LIKE 'DC-%'"
        )->fetch();
        return sprintf('DC-%05d', $row['n'] ?? 1);
    }

    public static function listAll(string $q = ''): array
    {
        $cols = 'dc.*, (SELECT COUNT(*) FROM delivery_challan_items WHERE challan_id = dc.id) AS item_count';
        if ($q === '') {
            return self::db()->query("SELECT {$cols} FROM delivery_challans dc ORDER BY id DESC")->fetchAll();
        }
        $st = self::db()->prepare("SELECT {$cols} FROM delivery_challans dc WHERE dc_no LIKE ? OR customer_name LIKE ? ORDER BY id DESC");
        $st->execute(["%$q%", "%$q%"]);
        return $st->fetchAll();
    }
}
