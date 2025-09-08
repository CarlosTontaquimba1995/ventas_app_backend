<?php

namespace App\Services\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
    public function register(array $data): User;
    public function login(array $credentials): ?array;
    public function logout(): void;
    public function refresh(): array;
    public function getAuthenticatedUser();
    public function verifyEmail(string $token): bool;
    public function forgotPassword(string $email): bool;
    public function resetPassword(array $data): bool;
    public function getUserByRefreshToken(string $token): ?User;
}
