<?php

namespace App\Enums;

// a Laravel specific base class
use Spatie\Enum\Laravel\Enum;

/**
 * @method static self MEMBER()
 * @method static self REQUESTOR()
 * @method static self PURCHASER()
 * @method static self DIRECTOR()
 * @method static self NONE()
 */
final class DepartmentRoleEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'MEMBER' => 0,
            'REQUESTOR' => 1,
            'PURCHASER' => 2,
            'DIRECTOR' => 3,
            'NONE' => 4,
        ];
    }

    /**
     * Get the enum value based on an integer.
     *
     * @param int $value
     * @return self|null
     */
    public static function fromValue(int $value): ?self
    {
        foreach (self::values() as $key => $enumValue) {
            if ($enumValue === $value) {
                return self::$key();
            }
        }

        return null;
    }
}
