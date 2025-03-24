<?php

namespace App\Enums;

enum Tenant: int {
    case OWNER = 1;
    case MEMBER = -1;

    public function label(): string {
        return match($this) {
            self::OWNER => 'Owner',
            self::MEMBER => 'Member',
        };
    }

    public function color(): string {
        return match($this) {
            self::OWNER => 'badge bg-success text-white',
            self::MEMBER => 'badge bg-info text-white',
        };
    }

    public static function printLabel($value): string {
        return match ($value) {
            self::OWNER->value => 'Owner',
            self::MEMBER->value => 'Member',
        };
    }

    public static function printColor($value): string {
        return match ($value) {
            self::OWNER->value => 'badge bg-success text-white',
            self::MEMBER->value => 'badge bg-info text-white',
        };
    }
}
