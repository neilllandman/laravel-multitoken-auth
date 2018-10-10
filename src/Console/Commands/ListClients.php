<?php

namespace Landman\MultiTokenAuth\Console\Commands;

use Illuminate\Console\Command;
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
        $this->table(['ID', 'Name', 'Api Client ID'], ApiClient::all(['id', 'name', 'value'])->toArray());
    }
}
