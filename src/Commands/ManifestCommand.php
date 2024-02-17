<?php

namespace Luminix\Backend\Commands;

use Illuminate\Console\Command;
use Luminix\Backend\Services\Manifest;

class ManifestCommand extends Command
{
    protected $signature = 'luminix:manifest
                            {--path= : The path to the manifest file}';

    protected $description = 'Create a manifest file for the Luminix backend';


    public function __construct(
        private Manifest $manifest,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        if (config('luminix.boot.includes_manifest_data', true)) {
            $this->info('Manifest data is already included in the boot process.');
            $this->info('Set "LUMINIX_BOOT_INCLUDES_MANIFEST_DATA" to false in your .env file to generate a manifest file.');
            return 1;
        }
        $this->info('Creating manifest file...');

        $manifest = [
            'models' => $this->manifest->models(),
            'routes' => $this->manifest->routes(),
        ];

        $filepath = $this->option('path') ?? resource_path('js/config/boot.json');

        file_put_contents($filepath, json_encode($manifest, JSON_PRETTY_PRINT));

        $this->info('Manifest file created successfully!');

        return 0;
    }
}
