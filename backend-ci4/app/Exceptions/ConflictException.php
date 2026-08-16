<?php

namespace App\Exceptions;

use DomainException;

/**
 * Dikirim ketika resource bertabrakan dengan data lain (mis. jadwal booking overlap).
 * Di-mapping ke HTTP 409 Conflict oleh controller.
 */
class ConflictException extends DomainException
{
}
