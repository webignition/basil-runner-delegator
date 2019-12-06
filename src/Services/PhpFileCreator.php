<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

class PhpFileCreator
{
    /**
     * @var string
     */
    private $outputDirectory = '';

    public function setOutputDirectory(string $outputDirectory): void
    {
        $this->outputDirectory = $outputDirectory;
    }

    public function create(string $className, string $code): string
    {
        $content =
            '<?php' . "\n\n" .
            'namespace webignition\BasilRunner\Generated;' . "\n\n" .
            $code .
            "\n";

        $filename = $className . '.php';
        $path = $this->outputDirectory . '/' . $filename;

        file_put_contents($path, $content);

        return $filename;
    }
}
