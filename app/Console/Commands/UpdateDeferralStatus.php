<?php

namespace App\Console\Commands;

use App\Models\Deferral;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\UserDetail;

class UpdateDeferralStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-deferral-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user deferral status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $deferralsToUpdate = Deferral::where('end_date', '<=', $now)
        ->where('status', '!=', 1)
        ->get();

        foreach ($deferralsToUpdate as $deferral) {
            $deferral->status = 1;
            $deferral->save();

            $user_detail = UserDetail::where('user_id', $deferral->user_id)->first();
            if ($user_detail) {
                $user_detail->remarks = 0;
                $user_detail->save();
            }
        }

        $this->info('Deferral statuses updated successfully.');
    }
}
