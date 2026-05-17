<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sigma:make-admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user an admin based on their email address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return;
        }

        $user->update(['role' => 'admin']);
        $this->info("User {$user->name} ({$email}) is now an admin.");
    }
}
