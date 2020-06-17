<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\RunCommand;

use webignition\BasilRunner\Model\ResultPrinter\TestName;

class ConsoleOutputFormatter
{
    public function format(string $line): string
    {
        if ($this->isTestNameLine($line)) {
            return $this->formatTestNameLine($line);
        }

        return $line;
    }

    private function isTestNameLine(string $line): bool
    {
        $pattern = '/^' . preg_quote(TestName::START, '/') . '.*' . preg_quote(TestName::END, '/') . '$/';

        return preg_match($pattern, $line) > 0;
    }

    private function formatTestNameLine(string $line): string
    {
        return preg_replace(
            [
                '/^' . preg_quote(TestName::START, '/') . '/',
                '/' . preg_quote(TestName::END, '/') . '$/',
            ],
            [
                '<options=bold>',
                '</>',
            ],
            $line
        );
    }
}
