<?php

namespace App\Services\Ai;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Str;

class AiTutorUserService
{
    public function resolve(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'ai-tutor@revisionhub.local'],
            [
                'role' => 'student',
                'name' => 'RevisionHub AI Tutor',
                'status' => UserStatus::DEACTIVE->value,
                'is_banned' => 'no',
                'password' => Str::random(40),
            ]
        );

        $needsUpdate = $user->role !== 'student'
            || $user->status !== UserStatus::DEACTIVE->value
            || $user->is_banned !== 'no'
            || $user->name !== 'RevisionHub AI Tutor';

        if ($needsUpdate) {
            $user->forceFill([
                'role' => 'student',
                'name' => 'RevisionHub AI Tutor',
                'status' => UserStatus::DEACTIVE->value,
                'is_banned' => 'no',
            ])->save();
        }

        if (blank($user->image)) {
            $user->image = 'uploads/website-images/avatar.png';
            $user->save();
        }

        return $user;
    }
}
