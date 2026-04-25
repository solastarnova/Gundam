<?php

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;

/** 處理收貨地址資料存取與驗證。 */
class AddressModel extends Model
{
    private const DEFAULT_ADDRESS_TYPE = '住宅';
    private const DEFAULT_UNIT_VALUE = '';

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

        if (empty($data['building'])) {
            return false;
        }

        if (empty($data['village_estate']) && empty($data['street'])) {
            return false;
        }

        return true;
    }

    public static function formatAddressAsOneLine(array $addr): string
    {
        return self::buildFullAddressLine($addr);
    }

    /** 組裝適用於運費 API 的單行標準化地址。 */
    public static function buildFullAddressLine(array $addr): string
    {
        $floor = trim((string) ($addr['floor'] ?? ''));
        $unit = self::normalizeUnitValue($addr['unit'] ?? null);

        $parts = array_filter([
            trim((string) ($addr['region'] ?? '')),
            trim((string) ($addr['district'] ?? '')),
            trim((string) ($addr['village_estate'] ?? '')),
            trim((string) ($addr['street'] ?? '')),
            trim((string) ($addr['building'] ?? '')),
            $floor !== '' ? self::appendSuffixIfMissing($floor, ['樓']) : '',
            $unit !== '' ? self::appendSuffixIfMissing($unit, ['室']) : '',
        ], fn ($v) => $v !== '');

        return implode(' ', $parts);
    }

    private static function appendSuffixIfMissing(string $value, array $suffixes): string
    {
        foreach ($suffixes as $suffix) {
            if (mb_substr($value, -mb_strlen($suffix)) === $suffix) {
                return $value;
            }
        }

        return $value . $suffixes[0];
    }

    private static function normalizeUnitValue($raw): string
    {
        $unit = trim((string) ($raw ?? ''));
        return $unit !== '' ? $unit : self::DEFAULT_UNIT_VALUE;
    }

    public function getUserAddresses(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$this->selectAddressFields()} FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC"
        );
        $stmt->execute([$userId]);
        $list = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $list[] = $row;
        }
        return $list;
    }

    public function getDefaultAddress(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$this->selectAddressFields()} FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAddressById(int $addressId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT {$this->selectAddressFields()} FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1"
        );
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
        $params = [
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
            self::normalizeUnitValue($data['unit'] ?? null),
        ];
        $stmt = $this->pdo->prepare("INSERT INTO user_addresses (user_id, address_label, is_default, recipient_name, phone,
            address_type, region, district, village_estate, street, building, floor, unit, lat, lng)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $params[] = $data['lat'] ?? null;
        $params[] = $data['lng'] ?? null;
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    /** 以驗證後資料更新既有地址。 */
    public function updateAddress(int $addressId, int $userId, array $data): bool
    {
        if (!self::validateHongKongAddress($data)) {
            throw new InvalidArgumentException('地址資料驗證失敗：必填欄位缺失或地址格式不正確');
        }
        if (!empty($data['is_default'])) {
            $this->unsetDefaultAddresses($userId, $addressId);
        }
        $params = [
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
            self::normalizeUnitValue($data['unit'] ?? null),
            $addressId,
            $userId
        ];
        $stmt = $this->pdo->prepare("UPDATE user_addresses SET address_label=?, is_default=?, recipient_name=?, phone=?,
            address_type=?, region=?, district=?, village_estate=?, street=?, building=?, floor=?, unit=?, lat=?, lng=?, updated_at=NOW()
            WHERE id = ? AND user_id = ?");
        array_splice($params, 12, 0, [$data['lat'] ?? null, $data['lng'] ?? null]);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            return true;
        }

        // No row changed may simply mean "same values as before".
        return $this->getAddressById($addressId, $userId) !== null;
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

    private function selectAddressFields(): string
    {
        $base = "id, address_label, is_default, recipient_name, phone, address_type,
                region, district, village_estate, street, building, floor, unit";
        $coord = "lat, lng";
        return $base . ", " . $coord . ", created_at, updated_at";
    }
}
