<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;

final readonly class AuthAccountResolution
{
    public const TYPE_ADMIN = 'admin';
    public const TYPE_USER = 'user';
    public const TYPE_REDIRECT = 'redirect';
    public const TYPE_NOT_FOUND = 'not_found';
    public const TYPE_INACTIVE = 'inactive';
    public const TYPE_BANNED = 'banned';

    public function __construct(
        public string $type,
        public ?User $user = null,
        public ?Admin $admin = null,
        public ?string $redirectRoute = null,
        public ?string $message = null,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    public function isUser(): bool
    {
        return $this->type === self::TYPE_USER;
    }

    public function isRedirect(): bool
    {
        return $this->type === self::TYPE_REDIRECT;
    }

    public function isNotFound(): bool
    {
        return $this->type === self::TYPE_NOT_FOUND;
    }

    public function isInactive(): bool
    {
        return $this->type === self::TYPE_INACTIVE;
    }

    public function isBanned(): bool
    {
        return $this->type === self::TYPE_BANNED;
    }

    public function account(): User|Admin|null
    {
        return $this->admin ?? $this->user;
    }
}
