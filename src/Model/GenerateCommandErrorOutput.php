<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class GenerateCommandErrorOutput extends AbstractGenerateCommandOutput implements \JsonSerializable
{
    private $errorMessage;

    public function __construct(string $source, string $target, string $errorMessage)
    {
        parent::__construct($source, $target);

        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function jsonSerialize(): array
    {
        return [
            'config' => [
                'source' => $this->getSource(),
                'target' => $this->getTarget(),
            ],
            'error' => $this->errorMessage,
        ];
    }

    public static function fromJson(string $json): GenerateCommandErrorOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'];

        return new GenerateCommandErrorOutput($configData['source'], $configData['target'], $data['error']);
    }
}
