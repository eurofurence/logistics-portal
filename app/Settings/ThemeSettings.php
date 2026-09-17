<?php

namespace App\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

class ThemeSettings extends Settings
{
    public string $primary_color = 'rgb(1,80,75)';

    public ?string $logo = null;

    public ?string $favicon = null;

    public ?string $social_image = null;

    /**
     * @return array{primary: string, foreground: string, surface: string}
     */
    public function emailColors(): array
    {
        $color = trim($this->primary_color);
        $rgb = [1, 80, 75];

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $color, $matches)) {
            $channels = array_map('intval', array_slice($matches, 1));
            if (max($channels) <= 255) {
                $rgb = $channels;
            }
        } elseif (preg_match('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $color, $matches)) {
            $hex = strlen($matches[1]) === 3
                ? implode('', array_map(fn (string $channel): string => $channel.$channel, str_split($matches[1])))
                : $matches[1];
            $rgb = array_map('hexdec', str_split($hex, 2));
        }

        $linear = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);
        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];

        return [
            'primary' => sprintf('#%02x%02x%02x', ...$rgb),
            'foreground' => $luminance > 0.179 ? '#000000' : '#ffffff',
            'surface' => sprintf('#%02x%02x%02x', ...array_map(fn (int $channel): int => (int) round(255 * 0.92 + $channel * 0.08), $rgb)),
        ];
    }

    public function faviconUrl(): string
    {
        return $this->favicon ? Storage::disk()->url($this->favicon) : asset('favicon.ico');
    }

    public function socialImageUrl(): string
    {
        return $this->social_image ? Storage::disk()->url($this->social_image) : $this->logoUrl();
    }

    public function logoUrl(): string
    {
        return $this->logo ? Storage::disk()->url($this->logo) : asset('images/logo-round-filled.png');
    }

    public static function group(): string
    {
        return 'theme';
    }
}
