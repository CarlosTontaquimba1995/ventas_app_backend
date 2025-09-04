<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        try {
            // User is already authenticated by JwtMiddleware
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Convert comma-separated roles to array if needed
            if (count($roles) === 1 && str_contains($roles[0], ',')) {
                $roles = explode(',', $roles[0]);
                $roles = array_map('trim', $roles);
            }

            // Ensure roles is an array
            $roles = is_array($roles) ? $roles : [$roles];

            // Get user's role (assuming it's stored in the 'role' column)
            $userRole = $user->role ?? null;

            if (!$userRole) {
                Log::warning('User has no role assigned', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'User role not found.'
                ], 403);
            }

            Log::debug('Role check', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'required_roles' => $roles,
                'has_role' => in_array($userRole, $roles)
            ]);

            // Check if user has any of the required roles
            if (!in_array($userRole, $roles)) {
                Log::warning('Unauthorized access attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'required_roles' => $roles,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource.'
                ], 403);
            }

            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('RoleMiddleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }
}
