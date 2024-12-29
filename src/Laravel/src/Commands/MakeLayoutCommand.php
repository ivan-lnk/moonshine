<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

use MoonShine\Laravel\Support\StubsPath;
use function Laravel\Prompts\{confirm, outro, text};

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'moonshine:layout')]
class MakeLayoutCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:layout {className?} {--compact} {--full} {--default} {--dir=}';

    protected $description = 'Create layout';

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

        $dir = $this->option('dir') ?: 'Layouts';

        $stubsPath->prependDir(
            $this->getDirectory($dir),
        )->prependNamespace(
            moonshineConfig()->getNamespace($dir),
        );

        $this->makeDir($stubsPath->dir);

        $compact = ! $this->option('full') && ($this->option('compact') || confirm('Want to use a minimalist theme?', false));

        $extendClassName = $compact ? 'CompactLayout' : 'AppLayout';
        $extends = "MoonShine\Laravel\Layouts\\$extendClassName";

        $this->copyStub('Layout', $stubsPath->getPath(), [
            '{namespace}' => $stubsPath->namespace,
            '{extend}' => $extends,
            '{extendShort}' => class_basename($extends),
            'DummyClass' => $stubsPath->name,
        ]);

        $this->wasCreatedInfo($stubsPath);

        if ($this->option('default') || confirm('Use the default template in the system?')) {
            $this->replaceInConfig(
                'layout',
                $stubsPath->getClassString()
            );
        }

        return self::SUCCESS;
    }
}
