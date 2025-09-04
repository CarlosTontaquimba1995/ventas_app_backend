<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    
    public function findByVerificationToken(string $token): ?User;
    
    public function findByResetToken(string $token): ?User;
    
    public function search(string $query, int $perPage = 10): LengthAwarePaginator;
    
    /**
     * Get the total count of users.
     *
     * @return int
     */
    public function count(): int;
    
    public function getUsersWithRole(string $role, array $columns = ['*']): Collection;
    
    public function getActiveUsersCount(): int;
    
    public function getNewUsersCount(int $days = 30): int;
    
    public function updateProfile(int $userId, array $data): bool;
    
    public function updatePassword(int $userId, string $password): bool;
    
    public function verifyEmail(string $token): bool;
    
    public function assignRole(int $userId, string $role): bool;
    
    public function hasRole(int $userId, string $role): bool;
    
    public function getUsersWithActiveCarts(): Collection;
    
    public function getUsersWithOrders(int $minOrders = 1): Collection;
    
    public function getTopCustomers(int $limit = 10): Collection;
    
    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator;
    
    public function attachOffer(int $userId, int $offerId, array $pivotData = []): bool;
    
    public function detachOffer(int $userId, int $offerId): bool;
    
    public function getOffers(int $userId): Collection;
    
    public function getActiveOffers(int $userId): Collection;
    
    public function hasOffer(int $userId, int $offerId): bool;
    
    public function updateVerificationToken(int $userId, ?string $token, bool $isActive = false): bool;
    
    public function updatePasswordResetToken(int $userId, ?string $token, ?string $expiresAt = null): bool;
    
    public function updateRefreshToken(int $userId, ?string $token, ?string $expiresAt = null): bool;
    
    public function getUserCart(int $userId): ?object;
    
    public function deleteById(int $id): bool;
}
