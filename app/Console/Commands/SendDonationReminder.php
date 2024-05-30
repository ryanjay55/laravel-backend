<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\DispensedEmail;
use Illuminate\Support\Facades\Mail;
use App\Models\BloodBag;
use Carbon\Carbon;

class SendDonationReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:donation-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send donation reminders to users who have not donated for 80 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Retrieve all users
        $users = User::all();

        foreach ($users as $user) {
            $userId = $user->user_id;

            $lastDonation = BloodBag::where('user_id', $userId)
                ->orderBy('date_donated', 'desc')
                ->first();

            if ($lastDonation) {
                $daysSinceLastDonation = now()->diffInDays($lastDonation->date_donated);
                // Check if it has been 80 days since the last donation
                if ($daysSinceLastDonation >= 1) {
                    // Queue donation reminder email to the user
                    $nextDonationDate = Carbon::parse($lastDonation->date_donated)->addDays(90)->format('Y-m-d');

                    Mail::to($user->email)->queue(new \App\Mail\DonationReminder($user, $lastDonation->date_donated, $nextDonationDate));

                    // Output to the console that an email has been queued for sending
                    $this->info("Queued donation reminder for: {$user->email}");
                } else {
                    // Output to the console that the user is not eligible for a reminder yet
                    $this->info("User: {$user->email} is not yet eligible for a reminder. Days since last donation: {$daysSinceLastDonation}");
                }
            } else {
                // Handle case where user has no donation history
                $this->info("No donation history for user: {$user->email}");
            }
        }
    }
}
