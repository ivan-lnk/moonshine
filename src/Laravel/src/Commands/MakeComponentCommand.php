<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

use MoonShine\Laravel\Support\StubsPath;
use function Laravel\Prompts\{text};

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'moonshine:component')]
class MakeComponentCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:component {className?}';

    protected $description = 'Create component';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $className = $this->argument('className') ?? text(
            'Class name',
            required: true
        );

        $stubsPath = new StubsPath($className, 'php');

        $view = $this->makeViewFromStub('admin.components', $stubsPath->name, $stubsPath->dir);

        $stubsPath->prependDir(
            $this->getDirectory('Components')
        )->prependNamespace(
            moonshineConfig()->getNamespace('Components')
        );

        $this->makeDir($stubsPath->dir);

        $this->copyStub('Component', $stubsPath->getPath(), [
            '{namespace}' => $stubsPath->namespace,
            '{view}' => $view,
            'DummyClass' => $stubsPath->name,
        ]);

        $this->wasCreatedInfo($stubsPath);

        return self::SUCCESS;
    }
}
