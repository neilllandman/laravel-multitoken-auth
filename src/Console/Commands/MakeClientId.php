<?php

namespace Landman\MultiTokenAuth\Console\Commands;

use Illuminate\Console\Command;
use Landman\MultiTokenAuth\Models\ClientId;

/**
 * Class MakeClientId
 * @package App\Console\Commands
 */
class MakeClientId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landman:tokens:makeId {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new client id';


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
     * @return mixed
     */
    public function handle()
    {
        $clientId = ClientId::make($this->argument('name'));
//$this->info("{$clientId");
        $this->info("Client Id: {$clientId->value}");
    }
}
