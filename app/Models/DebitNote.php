<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

final class DebitNote extends Model
{
    protected static string $table = 'debit_notes';

    public static function withItems(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT dn.*, s.address AS supplier_address, s.phone AS supplier_phone, s.email AS supplier_email, p.ref_no AS purchase_ref_no, p.purchase_date AS purchase_date '
            . 'FROM debit_notes dn '
            . 'LEFT JOIN suppliers s ON dn.supplier_id = s.id '
            . 'LEFT JOIN purchases p ON dn.purchase_id = p.id '
            . 'WHERE dn.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $items = self::db()->prepare(
            'SELECT di.*, pr.hsn, pr.unit, tt.percentage AS tax_pct, tt.typeofduty '
            . 'FROM debit_note_items di '
            . 'LEFT JOIN products pr ON di.product_id = pr.id '
            . 'LEFT JOIN taxtypes tt ON pr.taxtype_id = tt.id '
            . 'WHERE di.debit_note_id = ?'
        );
        $items->execute([$id]);
        $row['items'] = $items->fetchAll();
        return $row;
    }

    public static function nextDnNo(): string
    {
        $row = self::db()->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(dn_no, 4) AS UNSIGNED)), 0) + 1 AS n FROM debit_notes WHERE dn_no LIKE 'DN-%'"
        )->fetch();
        return sprintf('DN-%05d', $row['n'] ?? 1);
    }

    public static function listAll(string $q = ''): array
    {
        $cols = 'dn.*, (SELECT COUNT(*) FROM debit_note_items WHERE debit_note_id = dn.id) AS item_count';
        if ($q === '') {
            return self::db()->query("SELECT {$cols} FROM debit_notes dn ORDER BY id DESC")->fetchAll();
        }
        $st = self::db()->prepare("SELECT {$cols} FROM debit_notes dn WHERE dn_no LIKE ? OR supplier_name LIKE ? ORDER BY id DESC");
        $st->execute(["%$q%", "%$q%"]);
        return $st->fetchAll();
    }
}
