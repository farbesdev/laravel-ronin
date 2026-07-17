<?php

declare(strict_types=1);

namespace Laravel\Ronin\Tests;

use Laravel\Ronin\Tests\User;
use Laravel\Ronin\Models\Role;
use Laravel\Ronin\Tests\TestCase;
use Laravel\Ronin\Middleware\UserHasRole;
use Laravel\Ronin\Middleware\UserHasAnyRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Ronin\Middleware\UserHasAllRoles;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function a_user_with_proper_role_can_access_route()
    {
        $user       = factory(User::class)->create();
        $editor     = factory(Role::class)->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $user->assignRoles($editor);

        $this->actingAs($user);

        $this->assertEquals($this->middleware(UserHasRole::class, 'editor'), 200);
    }

    #[Test]
    public function a_user_without_the_proper_role_can_not_access_route()
    {
        $this->expectException(HttpException::class);

        $user       = factory(User::class)->create();
        $editor     = factory(Role::class)->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $user->assignRoles($editor);
        
        $this->actingAs($user);

        $this->assertEquals($this->middleware(UserHasRole::class, 'admin'), 401);
    }

    #[Test]
    public function a_user_with_any_of_the_defined_roles_can_access_route()
    {
        $user  = factory(User::class)->create();
        $admin = factory(Role::class)->create([
            'name'    => 'Admin',
            'slug'    => 'admin',
            'special' => 'all-access',
        ]);

        $user->assignRoles($admin);

        $this->actingAs($user);

        $this->assertEquals($this->middleware(UserHasAnyRole::class, ['admin', 'editor']), 200);
    }

    #[Test]
    public function a_user_with_out_any_of_the_defined_roles_can_not_access_route()
    {
        $this->expectException(HttpException::class);

        $user  = factory(User::class)->create();

        $this->actingAs($user);

        $this->assertEquals($this->middleware(UserHasAnyRole::class, ['admin', 'editor']), 401);
    }

    #[Test]
    public function a_user_with_all_of_the_defined_roles_can_access_route()
    {
        $user  = factory(User::class)->create();
        $admin = factory(Role::class)->create([
            'name'    => 'Admin',
            'slug'    => 'admin',
            'special' => 'all-access',
        ]);

        $editor = factory(Role::class)->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $user->assignRoles($admin, $editor);

        $this->actingAs($user);

        $this->assertEquals($this->middleware(UserHasAllRoles::class, ['admin', 'editor']), 200);
    }

    #[Test]
    public function a_user_with_out_all_of_the_defined_roles_can_not_access_route()
    {
        $this->expectException(HttpException::class);
        
        $user   = factory(User::class)->create();
        $editor = factory(Role::class)->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $user->assignRoles($editor);

        $this->actingAs($user);

        $this->assertEquals($this->middleware(UserHasAllRoles::class, ['admin', 'editor']), 401);
    }
}