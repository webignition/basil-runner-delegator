<?php declare(strict_types=1);

namespace webignition\BasilRunner\DependencyInjection;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AddCommandsToApplicationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder)
    {
        $applicationDefinition = $containerBuilder->getDefinition(Application::class);

        foreach ($containerBuilder->getDefinitions() as $name => $definition) {
            $definitionClass = $definition->getClass();

            if (is_string($definitionClass) && is_a($definitionClass, Command::class, true)) {
                $applicationDefinition->addMethodCall('add', [new Reference($name)]);
            }
        }
    }
}
