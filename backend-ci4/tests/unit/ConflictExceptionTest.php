<?php

namespace App\Tests\Unit;

use App\Exceptions\ConflictException;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ConflictExceptionTest extends TestCase
{
    public function testExtendsDomainException(): void
    {
        $exception = new ConflictException('Vehicle or driver already has an overlapping booking.');

        // Controller lama yang masih menangkap DomainException tetap dapat menangani exception ini.
        $this->assertInstanceOf(DomainException::class, $exception);
    }

    public function testCarriesTheOriginalMessage(): void
    {
        $exception = new ConflictException('Vehicle or driver already has an overlapping booking.');

        $this->assertSame('Vehicle or driver already has an overlapping booking.', $exception->getMessage());
    }
}
