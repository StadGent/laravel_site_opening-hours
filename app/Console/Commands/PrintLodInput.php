<?php


namespace App\Console\Commands;

use App\Repositories\LodServicesRepository;

class PrintLodInput extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openinghours:print-lod-input {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print the lod input for development purposes.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->argument('type');
        $output = (new LodServicesRepository())->fetchServices($type);
        echo count($output).PHP_EOL;
    }
}
