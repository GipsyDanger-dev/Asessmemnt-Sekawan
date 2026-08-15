<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    public function login(): ResponseInterface
    {
        $rules = [
            'email' => 'required|valid_email|max_length[191]',
            'password' => 'required|string',
        ];

        if (! $this->validateData($this->request->getJSON(true) ?? [], $rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Validation failed.',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $input = $this->validator->getValidated();
        $user = (new UserModel())
            ->select('users.*, roles.code AS role_code')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.email', $input['email'])
            ->first();

        if ($user === null || ! $user['is_active'] || ! password_verify($input['password'], $user['password_hash'])) {
            service('activityLog')->record(null, 'LOGIN_FAILED', 'auth', null, 'Failed login attempt for '.$input['email'].'.', $this->request->getIPAddress());
            return $this->response->setStatusCode(401)->setJSON(['message' => 'Invalid email or password.']);
        }

        service('activityLog')->record((int) $user['id'], 'LOGIN_SUCCEEDED', 'auth', (int) $user['id'], 'User signed in.', $this->request->getIPAddress());

        return $this->response->setJSON([
            'token' => service('jwtService')->issue($user),
            'token_type' => 'Bearer',
            'expires_in' => (int) env('JWT_TTL', 3600),
            'user' => $this->publicUser($user),
        ]);
    }

    public function me(): ResponseInterface
    {
        $claims = service('jwtService')->authenticatedUser();

        return $this->response->setJSON(['user' => $claims]);
    }

    /** @param array<string, mixed> $user */
    private function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role_code'],
            'approval_level' => $user['approval_level'] === null ? null : (int) $user['approval_level'],
        ];
    }
}
