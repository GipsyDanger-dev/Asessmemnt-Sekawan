<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = service('jwtService')->authenticatedUser();
        $allowedRoles = is_array($arguments) ? $arguments : [];

        if ($user === null) {
            return service('response')->setStatusCode(401)->setJSON(['message' => 'Unauthenticated.']);
        }

        if (! in_array($user['role'] ?? null, $allowedRoles, true)) {
            return service('response')->setStatusCode(403)->setJSON(['message' => 'Forbidden.']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
