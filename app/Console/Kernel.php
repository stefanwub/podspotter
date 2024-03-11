<?php

namespace App\Console;

use App\Jobs\ImportPodcasts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('app:import-new-episodes')->everyFifteenMinutes();
        // $schedule->command('app:run-whisper-jobs')->everyMinute();
        $schedule->command('app:create-episode-whisper-jobs')->everyFiveMinutes();
        $schedule->job(ImportPodcasts::class)->daily();

        $schedule->command('app:create-episode-index')->everyFiveMinutes();

        $schedule->command('app:add-whisper-jobs-to-gpus')->everyMinute();

        $schedule->command('queue:prune-batches')->daily();

        // $schedule->command('app:embed-indexed-episodes')->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
