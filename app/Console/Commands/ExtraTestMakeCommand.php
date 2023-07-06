<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\TestMakeCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;

class ExtraTestMakeCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:e-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class in a custom path';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Test';

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        $path = $this->option('path');
        if (!is_null($path)) {
            if ($path) {
                return $rootNamespace . '\\' . $path;
            }

            return $rootNamespace;
        }

        if ($this->option('unit')) {
            return $rootNamespace . '\Unit';
        }

        return $rootNamespace . '\Feature';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['path', 'l', InputOption::VALUE_OPTIONAL, 'Create a test in path.'],
        ]);
    }
}
