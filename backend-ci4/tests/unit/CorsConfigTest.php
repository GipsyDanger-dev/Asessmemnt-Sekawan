<?php

namespace App\Tests\Unit;

use Config\Cors;
use PHPUnit\Framework\TestCase;

class CorsConfigTest extends TestCase
{
    public function testAllowsBothLocalFrontendOrigins(): void
    {
        $origins = (new Cors())->default['allowedOrigins'];

        $this->assertContains('http://localhost:5173', $origins);
        $this->assertContains('http://127.0.0.1:5173', $origins);
    }
}
