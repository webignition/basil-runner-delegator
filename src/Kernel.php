<?php

declare(strict_types=1);

namespace webignition\BasilRunner;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use webignition\BasilRunner\DependencyInjection\AddCommandsToApplicationCompilerPass;

final class Kernel extends BaseKernel
{
    public function registerBundles(): array
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/../config/services.yaml');
    }

    protected function build(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new AddCommandsToApplicationCompilerPass());
    }
}
