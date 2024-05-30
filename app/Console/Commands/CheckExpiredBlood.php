<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BloodBag;
use Illuminate\Support\Facades\Mail;

class CheckExpiredBlood extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-expired-blood';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bloodBag = BloodBag::where('expiration_date', '<=', now())
            ->where('isUsed', 0)
            ->where('isExpired', 0)
            ->where('isDisposed', 0)
            ->get();


        $recipients = [
            'ryanjayantonio304@gmail.com',
            'renzlander@gmail.com',
            'raymatthewreyes02@gmail.com',
            'robles04curt@gmail.com'
        ];

        $count = $bloodBag->count();
        foreach ($bloodBag as $bag) {
            $bag->isExpired = 1;
            $bag->save();
        }

        Mail::to($recipients)->queue(new \App\Mail\ExpiredBloodReminder($count));


    }
}
