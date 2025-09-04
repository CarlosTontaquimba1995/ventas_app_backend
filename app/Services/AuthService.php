<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(array $data): User
    {
        // Ensure the role is valid
        $validRoles = ['admin', 'salesperson', 'customer'];
        $role = $data['role'] ?? 'customer';
        
        if (!in_array($role, $validRoles)) {
            $role = 'customer'; // Default to customer if invalid role
        }
        
        // Check if this is the first user (make them admin)
        $userCount = $this->userRepository->count();
        if ($userCount === 0) {
            $role = 'admin';
        } elseif ($role === 'admin') {
            // Prevent non-first users from registering as admin
            $role = 'customer';
        }
        
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'role' => $role,
            'email_verification_token' => Str::random(60),
            'is_active' => false,
        ];

        $user = $this->userRepository->create($userData);

        // In a production environment, you might want to queue the email
        // Mail::to($user->email)->queue(new EmailVerificationMail($user));

        return $user;
    }

    public function login(array $credentials): ?array
    {
        try {
            // First, attempt to authenticate the user
            if (!$token = JWTAuth::attempt($credentials)) {
                Log::error('Invalid login attempt', [
                    'email' => $credentials['email'],
                    'time' => now()->toDateTimeString()
                ]);
                return null;
            }

            // Get the authenticated user
            $user = auth('api')->user();
            
            if (!$user) {
                Log::error('User not found after successful authentication');
                return null;
            }
            
            Log::info('User login successful', ['user_id' => $user->id, 'email' => $user->email]);

            // Check if user is active
            if (!$user->is_active) {
                auth('api')->logout();
                throw new \Exception('Please verify your email address before logging in.');
            }

            // Return the token and user data
            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
                'user' => $user
            ];
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            Log::error('Token expired', ['error' => $e->getMessage()]);
            throw new \Exception('Token has expired');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            Log::error('Invalid token', ['error' => $e->getMessage()]);
            throw new \Exception('Token is invalid');
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            Log::error('JWT Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Could not create token: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function logout(): void
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            throw new \Exception('Failed to logout, please try again.');
        }
    }

    public function refresh(): array
    {
        try {
            return $this->respondWithToken(JWTAuth::refresh());
        } catch (JWTException $e) {
            throw new \Exception('Could not refresh token');
        }
    }

    public function getAuthenticatedUser()
    {
        return JWTAuth::user();
    }

    public function verifyEmail(string $token): bool
    {
        $user = $this->userRepository->findByVerificationToken($token);

        if (!$user) {
            return false;
        }

        return $this->userRepository->updateVerificationToken($user->id, null, true);
    }

    public function forgotPassword(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            // Don't reveal if the user exists for security reasons
            return true;
        }

        $token = Str::random(60);
        $this->userRepository->updatePasswordResetToken(
            $user->id, 
            $token, 
            now()->addHours(1)->toDateTimeString()
        );

        // In a production environment, you might want to queue the email
        // Mail::to($user->email)->queue(new PasswordResetMail($user, $token));

        return true;
    }

    public function resetPassword(array $data): bool
    {
        $user = $this->userRepository->findByResetToken($data['token']);

        if (!$user || $user->password_reset_token_expires_at < now()) {
            return false;
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null
        ]);

        return true;
    }

    protected function respondWithToken($token): array
    {
        $user = JWTAuth::user();
        
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'refresh_token' => $this->createRefreshToken($user),
            'refresh_token_expires_in' => config('jwt.refresh_ttl') * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => (bool)$user->is_active
            ]
        ];
    }

    protected function createRefreshToken(User $user): string
    {
        $refreshToken = Str::random(80);
        
        $this->userRepository->updateRefreshToken(
            $user->id,
            $refreshToken,
            now()->addSeconds(config('jwt.refresh_ttl') * 60)->toDateTimeString()
        );
        
        return $refreshToken;
    }
}
