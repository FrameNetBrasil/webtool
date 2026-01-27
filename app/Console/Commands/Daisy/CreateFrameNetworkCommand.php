<?php

namespace App\Console\Commands\Daisy;

use App\Services\AppService;
use App\Services\Daisy\NetworkService;
use Illuminate\Console\Command;

class CreateFrameNetworkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daisy:create-frame-network';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the Daisy frame network by generating frame nodes and their relationships';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        AppService::setCurrentLanguage(1);
        NetworkService::init();
        $this->info('Creating frame network...');
        NetworkService::createFrameNetwork();
        $this->info('Creating fe-f network...');
        NetworkService::createFEFrameNetwork();
        $this->info('Creating evokes network...');
        NetworkService::createLUFrameNetwork();
        $this->info('Creating qualia network...');
        NetworkService::createQualiaNetwork();
        $this->info('Daisy network created successfully!');

        return Command::SUCCESS;
    }
}
