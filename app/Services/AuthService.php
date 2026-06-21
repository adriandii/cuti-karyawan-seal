<?php

namespace App\Services;

use App\Models\User;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthService
{
    public function register(array $data): User
    {
        $employeeRole = Roles::where('name', 'employee')->first();

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $employeeRole->id,
        ]);
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load('role'),
            'token' => $token,
        ];
    }

    public function loginWithProvider(SocialiteUser $providerUser, string $provider): array
{
    $user = User::where('provider', $provider)
        ->where('provider_id', $providerUser->getId())
        ->first();

    if (! $user) {
        $user = User::where('email', $providerUser->getEmail())->first();

        if ($user) {
            $user->update([
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
            ]);
        } else {
            $employeeRole = Roles::where('name', 'employee')->first();

            $user = User::create([
                'name' => $providerUser->getName(),
                'email' => $providerUser->getEmail(),
                'password' => null,
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
                'role_id' => $employeeRole->id,
            ]);
        }
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return [
        'user' => $user->load('role'),
        'token' => $token,
    ];
}

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}