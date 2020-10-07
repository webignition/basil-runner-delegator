<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\RunnerClient;

class ConfigurationFactory
{
    public const ENV_HOST_SUFFIX = '_RUNNER_HOST';
    public const ENV_PORT_SUFFIX = '_RUNNER_PORT';

    /**
     * @param array<mixed> $env
     *
     * @return Configuration[]
     */
    public function createCollectionFromEnv(array $env): array
    {
        $configurationData = $this->findConfigurationDataFromEnv($env);
        $configurations = [];

        foreach ($configurationData as $name => $clientData) {
            $configurations[$name] = Configuration::fromArray($clientData);
        }

        return $configurations;
    }

    /**
     * @param array<mixed> $env
     *
     * @return array<mixed>
     */
    private function findConfigurationDataFromEnv(array $env): array
    {
        $configurations = [];

        foreach ($env as $key => $value) {
            if (false === is_string($value)) {
                continue;
            }

            $hostMatch = $this->findRunnerConfigurationComponent(self::ENV_HOST_SUFFIX, 'host', $key, $value);
            if ([] !== $hostMatch) {
                $configurations = array_merge_recursive($configurations, $hostMatch);
            }

            $portMatch = $this->findRunnerConfigurationComponent(self::ENV_PORT_SUFFIX, 'port', $key, $value);
            if ([] !== $portMatch) {
                $configurations = array_merge_recursive($configurations, $portMatch);
            }
        }

        return $configurations;
    }

    /**
     * @param string $suffix
     * @param string $component
     * @param string $key
     * @param string $value
     *
     * @return array<mixed>
     */
    private function findRunnerConfigurationComponent(
        string $suffix,
        string $component,
        string $key,
        string $value
    ): array {
        $result = [];

        $matchPattern = '/^[A-Z]+' . $suffix . '$/';
        if (preg_match($matchPattern, $key)) {
            $replacePattern = '/' . $suffix . '$/';

            $identifier = (string) preg_replace($replacePattern, '', $key);
            $identifier = strtolower($identifier);

            $result[$identifier][$component] = $value;
        }

        return $result;
    }
}
