<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new LogicException('Demo accounts cannot be seeded in production.');
        }

        $this->upsertDemoUser(
            name: 'Demo Educator',
            email: 'educator@demo.quickquiz.test',
            role: UserRole::Educator,
        );

        $this->upsertDemoUser(
            name: 'Demo Learner',
            email: 'learner@demo.quickquiz.test',
            role: UserRole::Learner,
        );
    }

    private function upsertDemoUser(string $name, string $email, UserRole $role): void
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = 'DemoQuickQuiz1!';
        $user->email_verified_at = now();
        $user->role = $role;
        $user->role_selected_at = now();
        $user->save();
    }
}
