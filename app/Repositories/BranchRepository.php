<?php
/**
 * Branch Repository
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

class BranchRepository
{
    public function getAll(): array
    {
        return Database::local()
            ->query("SELECT * FROM `branches` ORDER BY `name`")
            ->fetchAll();
    }

    public function getActive(): array
    {
        return Database::local()
            ->query("SELECT * FROM `branches` WHERE `is_active` = 1 ORDER BY `name`")
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::local()->prepare("SELECT * FROM `branches` WHERE `id` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = Database::local()->prepare("SELECT * FROM `branches` WHERE `code` = ?");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(array $data): int
    {
        $pdo = Database::local();
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare(
                "UPDATE `branches`
                    SET `code` = :code, `name` = :name, `address` = :address,
                        `phone` = :phone, `is_active` = :is_active
                  WHERE `id` = :id"
            );
            $stmt->execute([
                ':code'      => $data['code'],
                ':name'      => $data['name'],
                ':address'   => $data['address'] ?? null,
                ':phone'     => $data['phone'] ?? null,
                ':is_active' => (int)($data['is_active'] ?? 1),
                ':id'        => $data['id'],
            ]);
            return (int)$data['id'];
        }
        $stmt = $pdo->prepare(
            "INSERT INTO `branches` (`code`, `name`, `address`, `phone`, `is_active`)
             VALUES (:code, :name, :address, :phone, :is_active)"
        );
        $stmt->execute([
            ':code'      => $data['code'],
            ':name'      => $data['name'],
            ':address'   => $data['address'] ?? null,
            ':phone'     => $data['phone'] ?? null,
            ':is_active' => (int)($data['is_active'] ?? 1),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $pdo = Database::local();
        $stmt = $pdo->prepare("DELETE FROM `branches` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }
}

