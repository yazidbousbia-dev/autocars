<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;

class ExpireOldListings extends Command
{
    protected $signature = 'cars:expire';

    protected $description = 'Mark approved car listings whose 10-day market window has passed as "expired"';

    public function handle(): int
    {
        $count = Car::where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$count} listing(s) as expired.");

        return self::SUCCESS;
    }
}
