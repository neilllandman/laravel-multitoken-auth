<?php

namespace Landman\MultiTokenAuth\Console\Commands;

use Illuminate\Console\Command;
use Landman\MultiTokenAuth\Models\ApiClient;

/**
 * Class MakeClientId
 * @package App\Console\Commands
 */
class DeleteClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landman:tokens:delete-client 
    {name : The name of the client}
    {--y|yes : Yes to prompt} 
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an API client.';


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
        $confirm = $this->askConfirmation();
        if ($confirm) {
            $client = $this->findClient();

            if ($client) {
                $client->delete();
                $this->info("Client {$client->name} deleted!");
            } else {
                $this->error("No client with name {$this->argument('name')} found!");
            }
            return;
        }
        // @codeCoverageIgnoreStart
        $this->info("Operation cancelled.");
    }
    // @codeCoverageIgnoreEnd

    /**
     * @return bool
     */
    protected function askConfirmation()
    {
        return $this->option('yes') || $this->confirm(
                'This will permanently delete this ID and all clients using it will no longer be able access your API. Continue?'
            );
    }

    /**
     * @return mixed
     */
    protected function findClient()
    {
        return ApiClient::where('name', $this->argument('name'))->first();
    }
}

