<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Admin;
use App\Models\User;

class AuthAccountResolverService
{
    public function resolvePublicLogin(string $email): AuthAccountResolution
    {
        if ($this->isAdminAccount($email)) {
            return new AuthAccountResolution(
                type: AuthAccountResolution::TYPE_REDIRECT,
                redirectRoute: 'admin.login',
                message: __('Admin accounts must sign in from the admin login page.'),
            );
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return new AuthAccountResolution(
                type: AuthAccountResolution::TYPE_NOT_FOUND,
                message: __('We could not find an account with that email address'),
            );
        }

        if ($user->status != UserStatus::ACTIVE->value) {
            return new AuthAccountResolution(
                type: AuthAccountResolution::TYPE_INACTIVE,
                user: $user,
                message: __('Inactive account'),
            );
        }

        if ($user->is_banned == UserStatus::BANNED->value) {
            return new AuthAccountResolution(
                type: AuthAccountResolution::TYPE_BANNED,
                user: $user,
                message: __('Your account has been banned'),
            );
        }

        return new AuthAccountResolution(
            type: AuthAccountResolution::TYPE_USER,
            user: $user,
        );
    }

    public function resolvePublicOtpAccount(string $email): AuthAccountResolution
    {
        return $this->resolvePublicLogin($email);
    }

    public function resolveAdminLogin(string $email): AuthAccountResolution
    {
        $admin = $this->resolveAdminAccount($email);

        if ($admin) {
            if ($admin->status !== 'active') {
                return new AuthAccountResolution(
                    type: AuthAccountResolution::TYPE_INACTIVE,
                    admin: $admin,
                    message: __('Inactive account'),
                );
            }

            return new AuthAccountResolution(
                type: AuthAccountResolution::TYPE_ADMIN,
                admin: $admin,
            );
        }

        if ($this->isRegularUserAccount($email)) {
            return new AuthAccountResolution(
                type: AuthAccountResolution::TYPE_REDIRECT,
                redirectRoute: 'login',
                message: __('Regular users must sign in from the user login page.'),
            );
        }

        return new AuthAccountResolution(
            type: AuthAccountResolution::TYPE_NOT_FOUND,
            message: __('Invalid Email'),
        );
    }

    public function resolveAdminOtpAccount(string $email): AuthAccountResolution
    {
        return $this->resolveAdminLogin($email);
    }

    public function isAdminAccount(string $email): bool
    {
        return Admin::where('email', $email)->exists()
            || User::where('email', $email)->where('role', 'admin')->exists();
    }

    public function isRegularUserAccount(string $email): bool
    {
        return User::where('email', $email)
            ->where('role', '!=', 'admin')
            ->exists();
    }

    public function resolveAdminAccount(string $email): ?Admin
    {
        $admin = Admin::where('email', $email)->first();

        if ($admin) {
            return $admin;
        }

        $userAdmin = User::where('email', $email)
            ->where('role', 'admin')
            ->first();

        if (! $userAdmin) {
            return null;
        }

        return Admin::updateOrCreate(
            ['email' => $userAdmin->email],
            [
                'name' => $userAdmin->name,
                'password' => $userAdmin->password,
                'status' => $userAdmin->status === UserStatus::ACTIVE->value ? 'active' : 'inactive',
            ]
        );
    }
}
