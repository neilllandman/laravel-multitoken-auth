<?php

namespace Landman\MultiTokenAuth\Console\Commands;

use Illuminate\Console\Command;
use Landman\MultiTokenAuth\Models\ApiClient;

/**
 * Class MakeClientId
 * @package App\Console\Commands
 */
class RefreshClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landman:tokens:refresh-client 
    {name : The name of the client}
    {--y|yes : Yes to prompt} 
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh an API client\'s value.';


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
        $confirm = $this->option('yes') || $this->confirm(
                'This will permanently change this ID and all clients using it will no longer be able access your API. Continue?'
            );
        if ($confirm) {
            $client = ApiClient::where('name', $this->argument('name'))->first();
            if ($client) {
                $client->remake();
                $this->line("Client updated!");
                $this->table(['Name', 'Api Client ID'], [ApiClient::latest()->first(['name', 'value'])->toArray()]);
            } else {
                $this->error("No client with name {$this->argument('name')} found!");
            }
            return;
        }
        $this->info('Operation cancelled.');
    }
}
