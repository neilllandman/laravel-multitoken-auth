<?php

namespace Landman\MultiTokenAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Landman\MultiTokenAuth\Models\ApiClient;

/**
 * Class MakeClientId
 * @package App\Console\Commands
 */
class ListClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landman:tokens:list-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List API clients.';


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
        $clients = ApiClient::all(['name', 'value'])->toArray();
        if (App::environment() !== 'production' && !empty(env('API_TEST_CLIENT_ID'))) {
            array_push($clients, [
                'name' => 'API_TEST_CLIENT_ID (From .env)',
                'value' => env('API_TEST_CLIENT_ID'),
            ]);
        }
        $this->table(['Name', 'Api Client ID'], $clients);
    }
}

