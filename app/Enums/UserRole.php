<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case RegionalDirector = 'regional_director';
    case FacilityManager = 'facility_manager';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::RegionalDirector => __('Regional Director'),
            self::FacilityManager => __('Facility Manager'),
        };
    }
}
