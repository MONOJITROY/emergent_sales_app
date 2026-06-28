<?php
namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table = '';

    protected static function db(): PDO { return Database::pdo(); }

    public static function all(string $orderBy = 'id DESC'): array
    {
        $sql = 'SELECT * FROM ' . static::$table . ' ORDER BY ' . $orderBy;
        return self::db()->query($sql)->fetchAll();
    }

    public static function find($id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::$table . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete($id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM ' . static::$table . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** Insert and return new id */
    public static function insert(array $data): int
    {
        $cols = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . static::$table . ' (' . implode(',', $cols) . ") VALUES ($placeholders)";
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)self::db()->lastInsertId();
    }

    public static function update($id, array $data): bool
    {
        $sets = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
        $sql = 'UPDATE ' . static::$table . " SET $sets WHERE id = ?";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([...array_values($data), $id]);
    }

    public static function search(array $columns, string $query, string $orderBy = 'id DESC'): array
    {
        if ($query === '') return self::all($orderBy);
        $where = implode(' OR ', array_map(fn($c) => "$c LIKE ?", $columns));
        $sql = 'SELECT * FROM ' . static::$table . " WHERE $where ORDER BY $orderBy";
        $stmt = self::db()->prepare($sql);
        $like = '%' . $query . '%';
        $stmt->execute(array_fill(0, count($columns), $like));
        return $stmt->fetchAll();
    }
}
