<?php

namespace Peekabooauth\PeekabooBundle\Services;

/**
 * Reads a JWT's "exp" claim WITHOUT verifying its signature.
 *
 * Only ever use the result to give up on a token early (skip the server call, cap a cache or cookie lifetime),
 * never to accept one: anybody can write any "exp" into an unsigned payload. Peekaboo still verifies every
 * token that is not already expired here.
 */
final class JwtExpiry
{
    /** Unix time the token expires at, or null when it is not a JWT or has no numeric "exp" claim. */
    public static function expiresAt(?string $token): ?int
    {
        $token = trim((string)$token);
        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        try {
            $payload = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $exp = is_array($payload) ? ($payload['exp'] ?? null) : null;
        if (is_int($exp)) {
            return $exp;
        }
        if (is_float($exp) || (is_string($exp) && is_numeric($exp))) {
            return (int)$exp;
        }

        return null;
    }

    /** True only when the token carries an "exp" that has passed; an unreadable token is left to the server. */
    public static function isExpired(?string $token, ?int $now = null): bool
    {
        $expiresAt = self::expiresAt($token);

        return $expiresAt !== null && $expiresAt <= ($now ?? time());
    }

    /**
     * Seconds a value derived from this token may be cached: $max, shortened to the token's remaining lifetime.
     * Never below 1, because a cache treats 0 as "forever" in some adapters.
     */
    public static function cacheTtl(?string $token, int $max, ?int $now = null): int
    {
        $expiresAt = self::expiresAt($token);
        if ($expiresAt === null) {
            return $max;
        }

        return max(1, min($max, $expiresAt - ($now ?? time())));
    }
}
