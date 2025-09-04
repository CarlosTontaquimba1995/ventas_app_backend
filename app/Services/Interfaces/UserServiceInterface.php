<?php

namespace App\Services\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserServiceInterface
{
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator;
    
    public function getUserById(int $id): ?User;
    
    public function getUserByEmail(string $email): ?User;
    
    public function createUser(array $data): User;
    
    public function updateUser(int $id, array $data): ?User;
    
    public function deleteUser(int $id): bool;
    
    public function updateUserStatus(int $id, bool $isActive): bool;
    
    public function updateUserRole(int $id, string $role): bool;
    
    public function searchUsers(string $query, int $perPage = 15): LengthAwarePaginator;
    
    public function getUsersByRole(string $role, int $perPage = 15): LengthAwarePaginator;
    
    public function addOfferToUser(int $userId, int $offerId, array $pivotData = []): bool;
    
    public function removeOfferFromUser(int $userId, int $offerId): bool;
    
    public function getUserOffers(int $userId): Collection;
    
    public function getUserActiveOffers(int $userId): Collection;
    
    public function verifyEmail(string $token): bool;
    
    public function updatePassword(int $userId, string $newPassword): bool;
}
