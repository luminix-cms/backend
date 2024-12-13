<?php

namespace Luminix\Backend\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeValidatorCommand extends GeneratorCommand
{
    protected $name = 'make:validator';

    protected $description = 'Create a new validator class';

    protected $type = 'Validator';

    protected function getStub()
    {
        return __DIR__ . '/../../stubs/validator.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Validators';
    }
}