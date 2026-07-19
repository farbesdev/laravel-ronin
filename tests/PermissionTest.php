<?php

declare(strict_types=1);

namespace Ronin\Tests;

use Ronin\Tests\User;
use Ronin\Exceptions\PermissionNotFoundException;
use PHPUnit\Framework\Attributes\Test;

class PermissionTest extends TestCase
{
    #[Test]
    public function a_non_existant_permission_should_throw_an_exception()
    {
        $this->expectException(PermissionNotFoundException::class);

        $user = factory(User::class)->create();
        
        $user->hasPermissionTo('i.dont.exist');
    }
}