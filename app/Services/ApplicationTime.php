<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class ApplicationTime
{
    public static function timezone(): string
    {
        return app(GeneralSettings::class)->timezone;
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone());
    }

    public static function local(DateTimeInterface|string|null $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value, config('app.timezone'))->setTimezone(self::timezone());
    }

    public static function startOfDayUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::timezone())->startOfDay()->utc();
    }

    public static function toUtc(DateTimeInterface|string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, self::timezone())->utc();
    }

    public static function startOfNextDayUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::timezone())->startOfDay()->addDay()->utc();
    }
}
