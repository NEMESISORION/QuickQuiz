<?php

namespace Tests\Feature\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class DemoUserSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_verified_educator_and_learner_accounts(): void
    {
        (new DemoUserSeeder)->setContainer($this->app)->run();

        $educator = User::query()->where('email', 'educator@demo.quickquiz.test')->firstOrFail();
        $learner = User::query()->where('email', 'learner@demo.quickquiz.test')->firstOrFail();
        $this->assertSame(UserRole::Educator, $educator->role);
        $this->assertSame(UserRole::Learner, $learner->role);
        $this->assertTrue($educator->hasVerifiedEmail());
        $this->assertTrue($learner->hasVerifiedEmail());
        $this->assertTrue(Hash::check('DemoQuickQuiz1!', $educator->password));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Demo accounts cannot be seeded in production.');

        (new DemoUserSeeder)->setContainer($this->app)->run();
    }
}
