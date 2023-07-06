<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Client;

class RefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the whole application to a new slate';

    /**
     * Old configuration
     *
     * @var array
     */
    public $old;

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
        $this->call('db:wipe');
        $this->call('migrate');

        $this->call('db:seed', [
            '--class' => 'DatabaseSeeder'
        ]);

        $this->old = $this->laravel['config']['passport.personal_access_client'];

        $clients = app(ClientRepository::class);

        $client = $clients->createPersonalAccessClient(
            null,
            'Platform`s Personal Access Client',
            config('app.url')
        );

        $this->info('Personal access client created successfully.');
        $this->updateEnvWithClientDetails($client);
        $this->info('');
        $this->info('ENV file updated with the new Personal access client credentials.');

        return 0;
    }

    public function updateEnvWithClientDetails(Client $client)
    {
        $contents = file_get_contents($this->laravel->environmentFilePath());

        if (
            !Str::contains($contents, 'PASSPORT_PERSONAL_ACCESS_CLIENT_ID') &&
            !Str::contains($contents, 'PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET')
        ) {
            $contents .= "\n";
        }

        if (!Str::contains($contents, 'PASSPORT_PERSONAL_ACCESS_CLIENT_ID')) {
            $contents .= PHP_EOL . 'PASSPORT_PERSONAL_ACCESS_CLIENT_ID=';
        }

        if (!Str::contains($contents, 'PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET')) {
            $contents .= PHP_EOL . 'PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=';
        }

        $contents = preg_replace(
            [
                $this->keyReplacementPattern('PASSPORT_PERSONAL_ACCESS_CLIENT_ID', $this->old['id']),
                $this->keyReplacementPattern('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET', $this->old['secret']),
            ],
            [
                'PASSPORT_PERSONAL_ACCESS_CLIENT_ID=' . $client->id,
                'PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=' . $client->plainSecret,
            ],
            $contents
        );

        file_put_contents($this->laravel->environmentFilePath(), $contents);
    }

    /**
     * Get a regex pattern that will match env $keyName with any key.
     *
     * @param  string $keyName
     * @return string
     */
    protected function keyReplacementPattern(string $key, ?string $value)
    {
        $escaped = preg_quote('=' . $value, '/');

        return "/^{$key}{$escaped}/m";
    }
}
