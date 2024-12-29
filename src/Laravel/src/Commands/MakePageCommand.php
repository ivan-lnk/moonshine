<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

use MoonShine\Laravel\Support\StubsPath;
use function Laravel\Prompts\{outro, select, text};

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'moonshine:page')]
class MakePageCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:page {className?} {--force} {--without-register} {--crud} {--dir=} {--extends=}';

    protected $description = 'Create page';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $extends = $this->option('extends') ?? 'Page';

        $className = $this->argument('className') ?? text(
            'Class name',
            required: true
        );

        $stubsPath = new StubsPath($className, 'php');

        $dir = $this->option('dir') ?: 'Pages';

        $stubsPath->prependDir(
            $this->getDirectory($dir),
        )->prependNamespace(
            moonshineConfig()->getNamespace($dir),
        );

        if (! $this->option('force') && ! $this->option('extends') && ! $this->option('crud')) {
            $types = [
                '' => 'Custom',
                'IndexPage' => 'IndexPage',
                'FormPage' => 'FormPage',
                'DetailPage' => 'DetailPage',
            ];

            $type = array_search(
                select('Type', $types),
                $types,
                true
            );

            $extends = $type ?: null;

            $this->makePage($stubsPath, $extends ? 'CrudPage' : 'Page', $extends);

            return self::SUCCESS;
        }

        if ($this->option('crud')) {
            $name = $stubsPath->name;

            foreach (['IndexPage', 'FormPage', 'DetailPage'] as $type) {
                $stubsPath = new StubsPath("$name$type", 'php');

                $stubsPath->prependDir(
                    $this->getDirectory("$dir/$name"),
                )->prependNamespace(
                    moonshineConfig()->getNamespace("$dir\\$name"),
                );

                $this->makePage($stubsPath, 'CrudPage', $type);
            }

            return self::SUCCESS;
        }

        $this->makePage($stubsPath, 'Page', $extends);

        return self::SUCCESS;
    }

    /**
     * @throws FileNotFoundException
     */
    private function makePage(
        StubsPath $stubsPath,
        string $stub = 'Page',
        ?string $extends = null,
    ): void {
        $extends = $extends ?: 'Page';

        $this->makeDir($stubsPath->dir);

        $this->copyStub($stub, $stubsPath->getPath(), [
            '{namespace}' => $stubsPath->namespace,
            'DummyClass' => $stubsPath->name,
            'DummyTitle' => $stubsPath->name,
            '{extendShort}' => $extends,
        ]);

        $this->wasCreatedInfo($stubsPath);

        if (! $this->option('without-register')) {
            self::addResourceOrPageToProviderFile(
                $stubsPath->name,
                page: true,
                namespace: $stubsPath->namespace
            );
        }
    }
}
