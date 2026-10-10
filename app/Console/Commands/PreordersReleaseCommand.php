<?php

namespace App\Console\Commands;

use App\Services\Preorders\PreorderReleaseService;
use Illuminate\Console\Command;

#[\Illuminate\Console\Attributes\Description('Attempt off-session charges for due preorder reservations and grant access on success.')]
#[\Illuminate\Console\Attributes\Signature('videocourses:preorders-release')]
class PreordersReleaseCommand extends Command
{
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
