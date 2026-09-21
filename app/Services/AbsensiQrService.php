<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * Stateless dynamic QR token used for attendance.
 *
 * A token is a short-lived value combining a time window and an HMAC signature
 * over it. No token is persisted: the server only recomputes the signature
 * with the shared app key to verify authenticity and freshness.
 */
class AbsensiQrService
{
    private const WINDOW_SECONDS = 10;

    /**
     * Generate a fresh token for the current time window.
     */
    public function generate(?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now();

        $window = $this->windowFor($now);

        return $window.'.'.$this->signature((string) $window);
    }

    /**
     * Verify that an offered token is well-formed, fresh, and signed by the server.
     *
     * The immediately previous window is accepted as a small grace period so a
     * scan started just before a window boundary is not rejected while the user
     * is mid-scan.
     */
    public function verify(string $token, ?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return false;
        }

        [$windowRaw, $signature] = $parts;

        if ($windowRaw === '' || $signature === '' || ! ctype_digit($windowRaw)) {
            return false;
        }

        $window = (int) $windowRaw;
        $current = $this->windowFor($now);

        if ($window !== $current && $window !== $current - 1) {
            return false;
        }

        return hash_equals($this->signature($windowRaw), $signature);
    }

    /**
     * Seconds before the current token is replaced by a new one.
     */
    public function expirySeconds(): int
    {
        return self::WINDOW_SECONDS;
    }

    /**
     * Seconds left in the current window before a fresh token is generated.
     */
    public function secondsUntilExpiry(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();

        $windowStart = $this->windowFor($now) * self::WINDOW_SECONDS;

        return max(1, $windowStart + self::WINDOW_SECONDS - $now->getTimestamp());
    }

    private function windowFor(CarbonImmutable $now): int
    {
        return intdiv($now->getTimestamp(), self::WINDOW_SECONDS);
    }

    private function signature(string $data): string
    {
        return substr(hash_hmac('sha256', $data, $this->secret()), 0, 32);
    }

    private function secret(): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            return $decoded !== false ? $decoded : $key;
        }

        return $key;
    }
}
