<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * @var User
     */
    protected $model;

    public function __construct(User $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }
    
    /**
     * Get the total count of users.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }


    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByVerificationToken(string $token): ?User
    {
        return $this->model->where('email_verification_token', $token)->first();
    }

    public function findByResetToken(string $token): ?User
    {
        return $this->model->where('reset_password_token', $token)
            ->where('reset_password_expires', '>', now())
            ->first();
    }
    
    public function findByRefreshToken(string $token): ?User
    {
        // The token is already hashed when stored in the database
        return $this->model->where('refresh_token', $token)
            ->where('refresh_token_expires_at', '>', now())
            ->first();
    }
    
    public function updateVerificationToken(int $userId, ?string $token, bool $isActive = false): bool
    {
        return $this->update($userId, [
            'email_verification_token' => $token,
            'is_active' => $isActive,
            'email_verified_at' => $isActive ? now() : null
        ]);
    }
    
    public function updatePasswordResetToken(int $userId, ?string $token, ?string $expiresAt = null): bool
    {
        return $this->update($userId, [
            'password_reset_token' => $token,
            'password_reset_token_expires_at' => $expiresAt
        ]);
    }
    
    public function updateRefreshToken(int $userId, ?string $token, ?string $expiresAt = null): bool
    {
        return $this->update($userId, [
            'refresh_token' => $token ? hash('sha256', $token) : null,
            'refresh_token_expires_at' => $expiresAt
        ]);
    }
    
    public function getUserCart(int $userId): ?\stdClass
    {
        $user = $this->findById($userId, ['*'], ['cart']);
        return $user->cart ?? null;
    }
    
    public function hasOffer(int $userId, int $offerId): bool
    {
        return $this->model->where('id', $userId)
            ->whereHas('offers', function ($query) use ($offerId) {
                $query->where('offer_id', $offerId);
            })
            ->exists();
    }

    public function paginate(int $perPage = 10, array $columns = ['*'], array $where = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->select($columns);
        
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }
        
        return $query->paginate($perPage);
    }

    public function search(string $query, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->paginate($perPage);
    }

    public function getUsersWithRole(string $role, array $columns = ['*']): Collection
    {
        return $this->model->where('role', $role)
            ->select($columns)
            ->get();
    }

    public function getActiveUsersCount(): int
    {
        return $this->model->where('last_login_at', '>=', now()->subDays(30))
            ->count();
    }

    public function getNewUsersCount(int $days = 30): int
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        
        return $user->update($data);
    }

    public function updatePassword(int $userId, string $password): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        
        return $user->update(['password' => bcrypt($password)]);
    }


    public function assignRole(int $userId, string $role): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        
        return $user->update(['role' => $role]);
    }

    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->findById($userId, ['role']);
        return $user ? $user->role === $role : false;
    }

    public function getUsersWithActiveCarts(): Collection
    {
        return $this->model->whereHas('cart', function ($query) {
            $query->has('items');
        })->with(['cart.items'])->get();
    }

    public function getUsersWithOrders(int $minOrders = 1): Collection
    {
        return $this->model->withCount('orders')
            ->having('orders_count', '>=', $minOrders)
            ->orderBy('orders_count', 'desc')
            ->get();
    }

    public function getTopCustomers(int $limit = 10): Collection
    {
        return $this->model->withCount(['orders as total_spent' => function ($query) {
            $query->select(DB::raw('COALESCE(SUM(total), 0)'));
        }])
        ->orderBy('total_spent', 'desc')
        ->limit($limit)
        ->get();
    }
    
    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('role', $role)
            ->orderBy('name')
            ->paginate($perPage);
    }
    
    public function attachOffer(int $userId, int $offerId, array $pivotData = []): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        
        $user->offers()->attach($offerId, $pivotData);
        return true;
    }
    
    public function detachOffer(int $userId, int $offerId): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        
        return $user->offers()->detach($offerId) > 0;
    }
    
    public function getOffers(int $userId): Collection
    {
        $user = $this->findById($userId);
        return $user ? $user->offers : new Collection();
    }
    
    public function getActiveOffers(int $userId): Collection
    {
        $user = $this->findById($userId);
        if (!$user) {
            return new Collection();
        }
        
        $now = now();
        return $user->offers()
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->where('expires_at', '>', $now)
                      ->orWhereNull('expires_at');
            })
            ->get();
    }
    
    public function verifyEmail(string $token): bool
    {
        $user = $this->findByVerificationToken($token);
        if (!$user) {
            return false;
        }
        
        return $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null
        ]);
    }
    
    public function deleteById(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }
        
        return (bool) $user->delete();
    }
}
