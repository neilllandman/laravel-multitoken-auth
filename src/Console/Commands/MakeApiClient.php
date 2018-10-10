<?php

namespace Landman\MultiTokenAuth\Console\Commands;

use Illuminate\Console\Command;
use Landman\MultiTokenAuth\Models\ApiClient;

/**
 * Class MakeClientId
 * @package App\Console\Commands
 */
class MakeApiClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landman:tokens:make-client {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API Client.';


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
        $clientId = ApiClient::make($this->argument('name'));
//$this->info("{$clientId");
        $this->table(['Name', 'Value'], [ApiClient::latest()->first(['name', 'value'])->toArray()]);
    }
}
