<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin
                            {--name= : Admin name}
                            {--email= : Admin email}
                            {--password= : Admin password}
                            {--update : Update existing user with same email}';

    protected $description = 'Create a single admin account for the portfolio dashboard';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Admin name', 'Admin');
        $email = $this->option('email') ?: $this->ask('Admin email');
        $password = $this->option('password') ?: $this->secret('Admin password (min 8 chars)');

        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Укажи корректный email.');

            return self::INVALID;
        }

        if (empty($password) || strlen($password) < 8) {
            $this->error('Пароль должен быть не короче 8 символов.');

            return self::INVALID;
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser && ! $this->option('update')) {
            $this->warn("Пользователь с email {$email} уже существует.");
            $this->line('Если нужно обновить имя/пароль, запусти команду с флагом --update.');

            return self::SUCCESS;
        }

        $user = $existingUser ?: new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->email_verified_at = now();
        $user->save();

        $action = $existingUser ? 'обновлён' : 'создан';
        $this->info("Админ {$action}: {$email}");

        return self::SUCCESS;
    }
}
