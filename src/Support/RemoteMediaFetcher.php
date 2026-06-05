<?php

namespace HasanHawary\MediaManager\Support;

class RemoteMediaFetcher
{
    private const DEFAULT_TIMEOUT_SECONDS = 10;

    private const DEFAULT_MAX_BYTES = 10485760;

    public function __construct(
        private readonly int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        private readonly int $maxBytes = self::DEFAULT_MAX_BYTES,
    ) {
    }

    public function isValidUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);
        if (! in_array($parts['scheme'] ?? null, ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }

        return $this->hostIsPublic($parts['host']);
    }

    public function fetch(string $url): ?RemoteMedia
    {
        if (! $this->isValidUrl($url)) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeoutSeconds,
                'follow_location' => 0,
                'ignore_errors' => false,
            ],
            'https' => [
                'timeout' => $this->timeoutSeconds,
                'follow_location' => 0,
                'ignore_errors' => false,
            ],
        ]);

        $stream = @fopen($url, 'rb', false, $context);
        if ($stream === false) {
            return null;
        }

        $content = stream_get_contents($stream, $this->maxBytes + 1);
        fclose($stream);

        if ($content === false || strlen($content) > $this->maxBytes) {
            return null;
        }

        return new RemoteMedia(
            $content,
            $this->headerValue($http_response_header ?? [], 'content-type'),
            $this->extensionFromUrl($url),
            $this->originalNameFromUrl($url),
        );
    }

    private function hostIsPublic(string $host): bool
    {
        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private function extensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension !== '' ? $extension : null;
    }

    private function originalNameFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $filename = basename($path);

        return $filename !== '' && $filename !== '.' ? $filename : null;
    }

    private function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $header) {
            if (str_starts_with(strtolower($header), strtolower($name).':')) {
                return trim(substr($header, strlen($name) + 1));
            }
        }

        return null;
    }
}
