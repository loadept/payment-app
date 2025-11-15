<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperUser extends Command
{
    protected $signature = 'make:superuser';
    protected $description = 'Create a new super user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('Enter email for super user');
        $password = $this->secret('Enter password for super user');

        $user = User::create([
            'name' => 'Super User',
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info('Super user created successfully.');
    }
}
