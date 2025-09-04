<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService implements UserServiceInterface
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function createUser(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        return $this->userRepository->create($data);
    }

    public function updateUser(int $id, array $data): ?User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $updated = $this->userRepository->update($id, $data);
        
        return $updated ? $this->getUserById($id) : null;
    }

    public function deleteUser(int $id): bool
    {
        return $this->userRepository->deleteById($id);
    }

    public function updateUserStatus(int $id, bool $isActive): bool
    {
        return $this->userRepository->update($id, ['is_active' => $isActive]);
    }

    public function updateUserRole(int $id, string $role): bool
    {
        return $this->userRepository->update($id, ['role' => $role]);
    }

    public function searchUsers(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->search($query, $perPage);
    }

    public function getUsersByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getByRole($role, $perPage);
    }

    public function addOfferToUser(int $userId, int $offerId, array $pivotData = []): bool
    {
        return $this->userRepository->attachOffer($userId, $offerId, $pivotData);
    }

    public function removeOfferFromUser(int $userId, int $offerId): bool
    {
        return $this->userRepository->detachOffer($userId, $offerId);
    }

    public function getUserOffers(int $userId): Collection
    {
        return $this->userRepository->getOffers($userId);
    }

    public function getUserActiveOffers(int $userId): Collection
    {
        return $this->userRepository->getActiveOffers($userId);
    }

    public function verifyEmail(string $token): bool
    {
        return $this->userRepository->verifyEmail($token);
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        return $this->userRepository->update($userId, [
            'password' => Hash::make($newPassword)
        ]);
    }
}
