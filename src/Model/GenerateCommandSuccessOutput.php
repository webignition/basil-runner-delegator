<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class GenerateCommandSuccessOutput extends AbstractGenerateCommandOutput implements \JsonSerializable
{
    private $output;

    public function __construct(string $source, string $target, array $output)
    {
        parent::__construct($source, $target);

        $this->output = $output;
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
                'source' => $this->getSource(),
                'target' => $this->getTarget(),
            ],
            'output' => $this->output,
        ];
    }

    public static function fromJson(string $json): GenerateCommandSuccessOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'];
        $outputData = $data['output'];

        $output = [];

        foreach ($outputData as $generatedTestOutput) {
            $output[] = GeneratedTestOutput::fromArray($generatedTestOutput);
        }

        return new GenerateCommandSuccessOutput($configData['source'], $configData['target'], $output);
    }
}
