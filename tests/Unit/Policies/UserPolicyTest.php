<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_user_can_update_own_profile(): void
    {
        $user = new User(['email' => 'user@example.com']);
        $user->id = 10;

        $this->assertTrue((new UserPolicy)->update($user, $user));
    }

    public function test_user_cannot_update_another_profile(): void
    {
        $user = new User(['email' => 'user@example.com']);
        $user->id = 10;
        $otherUser = new User(['email' => 'other@example.com']);
        $otherUser->id = 20;

        $this->assertFalse((new UserPolicy)->update($user, $otherUser));
    }
}
