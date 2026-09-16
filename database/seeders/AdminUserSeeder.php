<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the first administrator.
     * Password comes from ADMIN_PASSWORD when present, otherwise a random one
     * is generated and printed once — it is never written to any file.
     */
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@creativemark.local');
        $name = (string) env('ADMIN_NAME', 'Creative Mark Admin');

        if ($existing = User::query()->where('email', $email)->first()) {
            $existing->update(['role' => 'admin', 'is_active' => true]);
            $this->command?->info("Admin already exists: {$email} (password unchanged)");

            return;
        }

        $password = (string) (env('ADMIN_PASSWORD') ?: Str::password(16, true, true, false));

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->command?->warn('==============================================');
        $this->command?->warn(' Admin created');
        $this->command?->warn(' Email:    '.$email);
        $this->command?->warn(' Password: '.$password);
        $this->command?->warn(' Store it in a password manager, then rotate it.');
        $this->command?->warn('==============================================');
    }
}
