<?php

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;

class AddressModel extends Model
{
    private const DEFAULT_ADDRESS_TYPE = '住宅';

    /**
     * Validate Hong Kong address required fields.
     *
     * @param array $data Address data
     * @return bool Validation result
     */
    public static function validateHongKongAddress(array $data): bool
    {
        if (empty($data['recipient_name'])) {
            return false;
        }

        if (empty($data['phone'])) {
            return false;
        }

        if (empty($data['region'])) {
            return false;
        }

        if (empty($data['district'])) {
            return false;
        }

        if (empty($data['building']) || empty($data['unit'])) {
            return false;
        }

        if (empty($data['village_estate']) && empty($data['street'])) {
            return false;
        }

        return true;
    }

    /**
     * Format address fields as single line (e.g. for checkout shipping).
     *
     * @param array $addr Address row (region, district, street, building, unit, etc.)
     * @return string
     */
    public static function formatAddressAsOneLine(array $addr): string
    {
        $parts = array_filter([
            $addr['region'] ?? '',
            $addr['district'] ?? '',
            $addr['street'] ?? '',
            $addr['village_estate'] ?? '',
            $addr['building'] ?? '',
            isset($addr['floor']) && (string) $addr['floor'] !== '' ? $addr['floor'] . '樓' : '',
            $addr['unit'] ?? '',
        ], fn ($v) => trim((string) $v) !== '');
        return implode(' ', $parts);
    }

    /**
     * Get user addresses.
     *
     * @param int $userId User ID
     * @return array Address list
     */
    public function getUserAddresses(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, address_label, is_default, recipient_name, phone, address_type,
                region, district, village_estate, street, building, floor, unit, created_at, updated_at
            FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        $list = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Get default address (e.g. for checkout shipping).
     *
     * @param int $userId User ID
     * @return array|null Default address or null
     */
    public function getDefaultAddress(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, address_label, is_default, recipient_name, phone, address_type,
                region, district, village_estate, street, building, floor, unit, created_at, updated_at
            FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get address by ID.
     *
     * @param int $addressId Address ID
     * @param int $userId User ID
     * @return array|null Address or null
     */
    public function getAddressById(int $addressId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, address_label, is_default, recipient_name, phone, address_type,
                region, district, village_estate, street, building, floor, unit, created_at, updated_at
            FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$addressId, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createAddress(int $userId, array $data): int
    {
        if (!self::validateHongKongAddress($data)) {
            throw new InvalidArgumentException('地址資料驗證失敗：必填欄位缺失或地址格式不正確');
        }
        if (!empty($data['is_default'])) {
            $this->unsetDefaultAddresses($userId);
        }
        $stmt = $this->pdo->prepare("INSERT INTO user_addresses (user_id, address_label, is_default, recipient_name, phone,
            address_type, region, district, village_estate, street, building, floor, unit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $data['address_label'] ?? null,
            !empty($data['is_default']) ? 1 : 0,
            $data['recipient_name'],
            $data['phone'],
            $data['address_type'] ?? self::DEFAULT_ADDRESS_TYPE,
            $data['region'],
            $data['district'],
            $data['village_estate'] ?? null,
            $data['street'] ?? null,
            $data['building'],
            $data['floor'] ?? null,
            $data['unit']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateAddress(int $addressId, int $userId, array $data): bool
    {
        if (!self::validateHongKongAddress($data)) {
            throw new InvalidArgumentException('地址資料驗證失敗：必填欄位缺失或地址格式不正確');
        }
        if (!empty($data['is_default'])) {
            $this->unsetDefaultAddresses($userId, $addressId);
        }
        $stmt = $this->pdo->prepare("UPDATE user_addresses SET address_label=?, is_default=?, recipient_name=?, phone=?,
            address_type=?, region=?, district=?, village_estate=?, street=?, building=?, floor=?, unit=?, updated_at=NOW()
            WHERE id = ? AND user_id = ?");
        $stmt->execute([
            $data['address_label'] ?? null,
            !empty($data['is_default']) ? 1 : 0,
            $data['recipient_name'],
            $data['phone'],
            $data['address_type'] ?? self::DEFAULT_ADDRESS_TYPE,
            $data['region'],
            $data['district'],
            $data['village_estate'] ?? null,
            $data['street'] ?? null,
            $data['building'],
            $data['floor'] ?? null,
            $data['unit'],
            $addressId,
            $userId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteAddress(int $addressId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function setDefaultAddress(int $addressId, int $userId): bool
    {
        $this->unsetDefaultAddresses($userId, $addressId);
        $stmt = $this->pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        return $stmt->rowCount() > 0;
    }

    private function unsetDefaultAddresses(int $userId, ?int $excludeAddressId = null): void
    {
        if ($excludeAddressId !== null) {
            $stmt = $this->pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
            $stmt->execute([$userId, $excludeAddressId]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
    }
}
