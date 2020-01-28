<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\GenerateCommand;

use webignition\BasilRunner\Model\GeneratedTestOutput;

class SuccessOutput extends AbstractOutput implements \JsonSerializable
{
    private const CODE = 0;

    /**
     * @var array<GeneratedTestOutput>
     */
    private $output;

    /**
     * @param Configuration $configuration
     * @param array<GeneratedTestOutput> $output
     */
    public function __construct(Configuration $configuration, array $output)
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
     * @return string[]
     */
    public function getTestPaths(): array
    {
        $targetDirectory = $this->getConfiguration()->getTarget();

        $testPaths = [];

        foreach ($this->getOutput() as $generatedTestOutput) {
            $testPaths[] = $targetDirectory .  '/' . $generatedTestOutput->getTarget();
        }

        return $testPaths;
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
            new Configuration(
                $configData['source'],
                $configData['target'],
                $configData['base-class']
            ),
            $output
        );
    }
}
