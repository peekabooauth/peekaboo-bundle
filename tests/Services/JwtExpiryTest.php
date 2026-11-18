<?php

namespace Peekabooauth\PeekabooBundle\Tests\Services;

use Peekabooauth\PeekabooBundle\Services\JwtExpiry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JwtExpiryTest extends TestCase
{
    /** A JWT-shaped token; the signature is never checked, so any third segment will do. */
    public static function jwt(array $payload): string
    {
        $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');

        return $b64('{"alg":"RS256","typ":"JWT"}') . '.' . $b64(json_encode($payload)) . '.c2ln';
    }

    public function testReadsExp(): void
    {
        self::assertSame(1700000000, JwtExpiry::expiresAt(self::jwt(['exp' => 1700000000, 'username' => 'a@b.c'])));
    }

    public function testAcceptsBearerPrefix(): void
    {
        self::assertSame(1700000000, JwtExpiry::expiresAt('Bearer ' . self::jwt(['exp' => 1700000000])));
    }

    public function testNumericStringAndFloatExp(): void
    {
        self::assertSame(1700000000, JwtExpiry::expiresAt(self::jwt(['exp' => '1700000000'])));
        self::assertSame(1700000000, JwtExpiry::expiresAt(self::jwt(['exp' => 1700000000.5])));
    }

    public static function unreadable(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'dev token' => ['dev'];
        yield 'two segments' => ['a.b'];
        yield 'payload not base64' => ['a.!!!.c'];
        yield 'payload not json' => ['a.' . base64_encode('nope') . '.c'];
        yield 'payload a scalar' => ['a.' . base64_encode('42') . '.c'];
        yield 'no exp' => [self::jwt(['username' => 'a@b.c'])];
        yield 'exp not numeric' => [self::jwt(['exp' => 'tomorrow'])];
    }

    #[DataProvider('unreadable')]
    public function testUnreadableTokenHasNoExpiry(?string $token): void
    {
        self::assertNull(JwtExpiry::expiresAt($token));
        self::assertFalse(JwtExpiry::isExpired($token), 'an unreadable token is left to the server');
    }

    public function testIsExpired(): void
    {
        $now = 1700000000;

        self::assertTrue(JwtExpiry::isExpired(self::jwt(['exp' => $now - 1]), $now));
        self::assertTrue(JwtExpiry::isExpired(self::jwt(['exp' => $now]), $now));
        self::assertFalse(JwtExpiry::isExpired(self::jwt(['exp' => $now + 1]), $now));
    }

    public function testCacheTtlIsCappedByRemainingLifetime(): void
    {
        $now = 1700000000;

        self::assertSame(600, JwtExpiry::cacheTtl(self::jwt(['exp' => $now + 3600]), 600, $now));
        self::assertSame(120, JwtExpiry::cacheTtl(self::jwt(['exp' => $now + 120]), 600, $now));
        self::assertSame(1, JwtExpiry::cacheTtl(self::jwt(['exp' => $now - 50]), 600, $now));
        self::assertSame(600, JwtExpiry::cacheTtl('dev', 600, $now));
    }
}
