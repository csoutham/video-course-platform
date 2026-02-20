<?php

namespace App\Console\Commands;

use App\Services\Preorders\PreorderReleaseService;
use Illuminate\Console\Command;

class PreordersReleaseCommand extends Command
{
    protected $signature = 'videocourses:preorders-release';

    protected $description = 'Attempt off-session charges for due preorder reservations and grant access on success.';

    public function handle(PreorderReleaseService $preorderReleaseService): int
    {
        if (! (bool) config('learning.preorders_enabled')) {
            $this->info('Preorders are disabled.');

            return self::SUCCESS;
        }

        $result = $preorderReleaseService->releaseDueReservations();

        $this->info(sprintf(
            'Processed %d reservation(s): %d charged, %d failed.',
            $result['processed'],
            $result['charged'],
            $result['failed'],
        ));

        return self::SUCCESS;
    }
}
