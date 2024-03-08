<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-queue {--queue=default}';

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
        dispatch(function() {
            sleep(30);

            return;
        })->onQueue($this->option('queue'));
    }
}
