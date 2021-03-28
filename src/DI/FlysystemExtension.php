<?php

declare(strict_types=1);

namespace Nextte\Flysystem\DI;

use League\Flysystem\Filesystem;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\Utils\Validators;

final class FlysystemExtension extends CompilerExtension
{
    private $defaults = [
        'filesystem' => [],
    ];

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->defaults);

        Validators::assertField($config, 'filesystem', 'array');

        // Register filesystems
        foreach ($config['filesystem'] as $filesystemName => $filesystemConfig) {
            $filesystemPrefix = $this->prefix('filesystem.' . $filesystemName);
            $adapterPrefix = $filesystemPrefix . '.adapter';

            Validators::assert($filesystemConfig, 'array', $filesystemPrefix);
            Validators::assertField($filesystemConfig, 'adapter', 'string|object', $adapterPrefix);

            $config = [];
            if (isset($filesystemConfig['config'])) {
                Validators::assert($filesystemConfig['config'], 'array', $filesystemPrefix . '.config');
                $config = $filesystemConfig['config'];
            }

            $autowired = true;
            if (isset($filesystemConfig['autowired'])) {
                Validators::assert($filesystemConfig['autowired'], 'bool', $filesystemPrefix . '.autowired');
                $autowired = $filesystemConfig['autowired'];
            }

            $adapterDefinition = $builder->addDefinition($adapterPrefix);

            Compiler::loadDefinition($adapterDefinition, $filesystemConfig['adapter']);

            $adapterDefinition->setAutowired(false);

            $builder->addDefinition($filesystemPrefix)
                ->setType(Filesystem::class)
                ->setAutowired($autowired)
                ->setArguments([
                    $adapterDefinition,
                    $config
                ]);
        }
    }
}
