<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Session;

class ExpireUserSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire the current user\'s session';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Session::regenerate();

        $this->info('Current user\'s session has been expired.');
    }
}
