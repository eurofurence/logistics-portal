<?php

namespace App\Services;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\HttpFoundation\IpUtils;

class ArticleImageDownloader
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** @return array{contents: string, extension: string} */
    public function download(string $url): array
    {
        for ($redirect = 0; $redirect <= 3; $redirect++) {
            $parts = parse_url($url);
            if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['https', 'http'], true)
                || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
                throw new RuntimeException('Invalid article image URL.');
            }
            $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
            if (! in_array($port, [80, 443], true)) {
                throw new RuntimeException('Invalid article image port.');
            }
            $host = trim($parts['host'], '[]');
            $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);
            foreach ($addresses as $address) {
                if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) || IpUtils::isPrivateIp($address)) {
                    throw new RuntimeException('Article images must use a public address.');
                }
            }
            $address = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];
            $response = Http::connectTimeout(3)->timeout(10)->withoutRedirecting()
                ->withHeaders(['Accept-Encoding' => 'identity'])
                ->withOptions([
                    'proxy' => '',
                    'decode_content' => false,
                    'curl' => [CURLOPT_RESOLVE => [$parts['host'].':'.$port.':'.$address]],
                    'progress' => static function (float $total, float $downloaded): void {
                        if ($total > self::MAX_BYTES || $downloaded > self::MAX_BYTES) {
                            throw new RuntimeException('Article image exceeds 10 MB.');
                        }
                    },
                ])->get($url);

            if ($response->redirect()) {
                if ($redirect === 3 || ! $response->header('Location')) {
                    throw new RuntimeException('Too many article image redirects.');
                }
                $url = (string) UriResolver::resolve(new Uri($url), new Uri($response->header('Location')));

                continue;
            }
            if (! $response->successful()) {
                throw new RuntimeException('Article image download failed (HTTP '.$response->status().').');
            }
            $contents = $response->body();
            if (strlen($contents) > self::MAX_BYTES) {
                throw new RuntimeException('Article image exceeds 10 MB.');
            }
            $image = @getimagesizefromstring($contents);
            $extension = match ($image['mime'] ?? '') {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => null,
            };
            if (! $extension || $image[0] * $image[1] > 20000000) {
                throw new RuntimeException('Invalid or excessively large article image.');
            }

            return ['contents' => $contents, 'extension' => $extension];
        }

        throw new RuntimeException('Article image download failed.');
    }

    /** @return non-empty-list<string> */
    private function resolve(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];
        foreach ($records ?: [] as $record) {
            if (isset($record['ip']) || isset($record['ipv6'])) {
                $addresses[] = $record['ip'] ?? $record['ipv6'];
            }
        }
        if ($addresses === []) {
            throw new RuntimeException('Article image host could not be resolved.');
        }

        return $addresses;
    }
}
