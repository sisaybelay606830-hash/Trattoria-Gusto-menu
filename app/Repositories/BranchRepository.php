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

    /**
     * Resolve a branch by code, ID, name, or slug.
     */
    public function resolveBranch(string|int|null $ref): ?array
    {
        if (empty($ref)) {
            return null;
        }
        $refStr = trim((string)$ref);
        if ($refStr === '') {
            return null;
        }

        // 1. Direct code lookup
        $b = $this->findByCode($refStr);
        if ($b) {
            return $b;
        }

        // 2. Numeric ID lookup
        if (is_numeric($refStr)) {
            $b = $this->findById((int)$refStr);
            if ($b) {
                return $b;
            }
        }

        // 3. Match against active branches
        $active = $this->getActive();
        foreach ($active as $item) {
            if (strcasecmp($item['code'], $refStr) === 0 || strcasecmp($item['name'], $refStr) === 0) {
                return $item;
            }
        }

        // 4. Normalized slug / substring comparison
        $cleanRef = preg_replace('/[^a-z0-9]/', '', strtolower($refStr));
        if ($cleanRef !== '') {
            foreach ($active as $item) {
                $cleanCode = preg_replace('/[^a-z0-9]/', '', strtolower($item['code']));
                $cleanName = preg_replace('/[^a-z0-9]/', '', strtolower($item['name']));
                if ($cleanCode === $cleanRef || str_contains($cleanCode, $cleanRef) || str_contains($cleanRef, $cleanCode) || str_contains($cleanName, $cleanRef)) {
                    return $item;
                }
            }
        }

        return null;
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

