<?php

declare(strict_types=1);

namespace Ronin\Tests;

use Illuminate\Support\Facades\Schema;
use Ronin\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GrumpyTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function it_returns_true()
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function it_has_necessary_tables()
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('role_user'));
        $this->assertTrue(Schema::hasTable('permission_role'));
        $this->assertTrue(Schema::hasTable('permission_user'));
    }
}