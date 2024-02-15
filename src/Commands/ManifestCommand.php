<?php

namespace Luminix\Backend\Commands;

use Illuminate\Console\Command;

class ManifestCommand extends Command
{
    protected $signature = 'luminix:manifest';

    protected $description = 'Create a manifest file for the Luminix backend';

    public function handle()
    {
        $this->info('Creating manifest file...');
        $this->info('Manifest file created successfully!');
    }
}
