<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\GenerateCommand;

use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GeneratedTestOutput;

class SuccessOutput extends AbstractOutput implements \JsonSerializable
{
    private const CODE = 0;

    /**
     * @var array<GeneratedTestOutput>
     */
    private $output;

    /**
     * @param GenerateCommandConfiguration $configuration
     * @param array<GeneratedTestOutput> $output
     */
    public function __construct(GenerateCommandConfiguration $configuration, array $output)
    {
        parent::__construct($configuration, self::STATUS_SUCCESS, self::CODE);

        $this->output = $output;
    }

    /**
     * @return GeneratedTestOutput[]
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $serializedData = parent::jsonSerialize();
        $serializedData['output'] = $this->output;

        return $serializedData;
    }

    public static function fromJson(string $json): SuccessOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'];
        $outputData = $data['output'];

        $output = [];

        foreach ($outputData as $generatedTestOutput) {
            $output[] = GeneratedTestOutput::fromArray($generatedTestOutput);
        }

        return new SuccessOutput(
            new GenerateCommandConfiguration(
                $configData['source'],
                $configData['target'],
                $configData['base-class']
            ),
            $output
        );
    }
}
