<?php

namespace App\Core;

/**
 * 集中管理會員與積分相關常數（PHP 單一真實來源）。
 */
final class Constants
{
    /** 預設會員等級鍵值。 */
    public const MEMBERSHIP_LEVEL_BRONZE = 'bronze';

    /** 一個貨幣單位對應的整數積分（用於累積、扣抵與結帳上限）。 */
    public const POINTS_PER_HKD = 1000;

    private function __construct()
    {
    }
}
