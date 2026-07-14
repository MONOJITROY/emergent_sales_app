<?php
namespace App\Models;

use App\Core\Model;

final class TransactionAllocation extends Model
{
    protected static string $table = 'transaction_allocations';

    public static function forTransaction(int $transactionId): array
    {
        $stmt = self::db()->prepare("SELECT * FROM transaction_allocations WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        return $stmt->fetchAll();
    }
}
