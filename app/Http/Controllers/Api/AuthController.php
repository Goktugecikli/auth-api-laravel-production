<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use App\Exceptions\DomainException;
use App\Support\ErrorCode;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponse; 

    public function register(RegisterRequest $request)
    {
       $user = User::query()->create([
            'name' => (string) $request->input('name'),
            'email' => (string) $request->input('email'),
            'password' => Hash::make((string) $request->input('password')),
        ]);

        $token = $user->createToken('api', ['profile:read', 'auth:logout'])->plainTextToken;


        return $this->ok([
            'token' => $token,
            'user'  => new UserResource($user),
        ], 201);

    }

    public function login(LoginRequest $request)
    {
        $user = User::query()->where('email', $request->input('email'))->first();

         if (!$user || !Hash::check((string) $request->input('password'), $user->password)) {
        throw new DomainException(ErrorCode::AUTH_INVALID);
        }

        $token = $user->createToken('api', ['profile:read', 'auth:logout'])->plainTextToken;


        return $this->ok([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }


    public function me(Request $request)
    {

        $user = $request->user();
        if (! $user || ! $user->currentAccessToken()) {
            abort(401);
        }

        if (! $user->tokenCan('profile:read')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

    
       return $this->ok([
            'user' => new UserResource($request->user()),
        ]);

    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan('auth:logout')) {
            throw new DomainException(ErrorCode::TOKEN_FORBIDDEN);
        }

        // 1) Normal durumda: current token sil
        $user->currentAccessToken()?->delete();

        // 2) Fallback: bearer token'dan id'yi çekip sil
        $bearer = $request->bearerToken(); // "id|plain..." formatı
        if ($bearer) {
            $tokenId = explode('|', $bearer, 2)[0] ?? null;

            if ($tokenId) {
                PersonalAccessToken::query()->where('id', $tokenId)->delete();
            }
        }

        return $this->ok(['message' => 'Logged out']);
    }

}
