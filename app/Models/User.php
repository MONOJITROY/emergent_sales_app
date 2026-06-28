<?php namespace App\Models; use App\Core\Model;
final class User extends Model { protected static string $table = 'users';
    public static function byEmail(string $email): ?array {
        $s = self::db()->prepare('SELECT * FROM users WHERE email = ?'); $s->execute([$email]);
        return $s->fetch() ?: null;
    }
}
