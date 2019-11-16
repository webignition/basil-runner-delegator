<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilCompiler\ExternalVariableIdentifiers;

class GenerateCommandOutput implements \JsonSerializable
{
    private $source;
    private $target;
    private $output;

    public function __construct(string $source, string $target, array $output)
    {
        $this->source = $source;
        $this->target = $target;
        $this->output = $output;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return GeneratedTestOutput[]
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    public function jsonSerialize(): array
    {
        return [
            'config' => [
                'source' => $this->source,
                'target' => $this->target,
            ],
            'output' => $this->output,
        ];
    }

    public static function fromJson(string $json): GenerateCommandOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'];
        $outputData = $data['output'];

        $output = [];

        foreach ($outputData as $generatedTestOutput) {
            $output[] = GeneratedTestOutput::fromArray($generatedTestOutput);
        }

        return new GenerateCommandOutput($configData['source'], $configData['target'], $output);
    }
}
