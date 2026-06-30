<?php
namespace App\Models;
use App\Core\Model;

final class Company extends Model
{
    protected static string $table = 'companies';

    public static function settings(): ?array
    {
        return self::find(1);
    }
}
