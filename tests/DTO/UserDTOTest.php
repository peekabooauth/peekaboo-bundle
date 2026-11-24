<?php

namespace Peekabooauth\PeekabooBundle\Tests\DTO;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;
use PHPUnit\Framework\TestCase;

class UserDTOTest extends TestCase
{
    public function testRolesAreExactlyThePeekabooGrantWithoutImplicitRoleUser(): void
    {
        $user = new UserDTO(['email' => 'key@example.com', 'name' => 'key', 'roles' => ['ROLE_METRICS']]);

        self::assertSame(['ROLE_METRICS'], $user->getRoles());
    }

    public function testNoGrantMeansNoRoles(): void
    {
        $user = new UserDTO(['email' => 'nobody@example.com', 'name' => 'nobody', 'roles' => []]);

        self::assertSame([], $user->getRoles());
    }

    public function testDuplicatesAreDroppedAndTheListStaysAList(): void
    {
        $user = new UserDTO(['email' => 'vl@example.com', 'name' => 'vl', 'roles' => ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']]);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testPeekabooObjectShapedRolesBecomeAList(): void
    {
        // Peekaboo sometimes serialises roles as {"1":"ROLE_ADMIN"} (a PHP array with holes)
        $user = new UserDTO(['email' => 'vl@example.com', 'name' => 'vl', 'roles' => [1 => 'ROLE_ADMIN', 3 => 'ROLE_API']]);

        self::assertSame(['ROLE_ADMIN', 'ROLE_API'], $user->getRoles());
    }
}
