<?php

namespace Receiver\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class GenerateReceiver extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'receiver:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a custom Receiver provider.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Receiver';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return $this->option('verified') === false
            ? __DIR__.'/../../../stubs/receiver.stub'
            : __DIR__.'/../../../stubs/receiver-verified.stub';
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     * @throws FileNotFoundException
     */
    protected function buildClass($name)
    {
        $class = class_basename(Str::studly(str_replace(['Receiver'], '', $name)));

        $namespace = $this->getNamespace(
            Str::replaceFirst($this->rootNamespace(), 'App\\Http\\Receivers\\', $this->qualifyClass($name))
        );

        $replace = [
            '{{ providerNamespace }}' => $namespace,
            '{{ providerClass }}' => $class,
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = (string) Str::of($name)->replaceFirst($this->rootNamespace(), '');

        return $this->laravel->basePath().'/app/Http/Receivers/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['verified', false, InputOption::VALUE_NONE, 'Webhooks are verified with a signature'],
        ];
    }
}
