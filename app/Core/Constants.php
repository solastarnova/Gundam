<?php

namespace App\Core;

/**
 * Application-wide literals for membership and points (single source of truth in PHP).
 */
final class Constants
{
    /** Default membership tier key when none is set or empty */
    public const MEMBERSHIP_LEVEL_BRONZE = 'bronze';

    /** Integer points equivalent to one currency unit (earn, spend, checkout caps, UI copy) */
    public const POINTS_PER_HKD = 1000;

    private function __construct()
    {
    }
}
