<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\RunCommand;

use webignition\BasilRunner\Model\ResultPrinter\Failure;
use webignition\BasilRunner\Model\ResultPrinter\StatusIcon;
use webignition\BasilRunner\Model\ResultPrinter\Success;
use webignition\BasilRunner\Model\ResultPrinter\TestName;
use webignition\BasilRunner\Model\TestOutput\IconMap;
use webignition\BasilRunner\Model\TestOutput\Status;

class ConsoleOutputFormatter
{
    public function format(string $line): string
    {
        if ($this->isTestNameLine($line)) {
            return $this->formatTestNameLine($line);
        }

        $line = str_replace(
            StatusIcon::SUCCESS,
            '<success>' . IconMap::get(Status::SUCCESS) . '</success>',
            $line
        );

        $line = str_replace(
            StatusIcon::FAILURE,
            '<failure>' . IconMap::get(Status::FAILURE) . '</failure>',
            $line
        );

        $line = str_replace(Success::START, '<fg=green>', $line);
        $line = str_replace(Success::END, '</>', $line);

        $line = str_replace(Failure::START, '<fg=red>', $line);
        $line = str_replace(Failure::END, '</>', $line);

        return $line;
    }

    private function isTestNameLine(string $line): bool
    {
        $pattern = '/^' . preg_quote(TestName::START, '/') . '.*' . preg_quote(TestName::END, '/') . '$/';

        return preg_match($pattern, $line) > 0;
    }

    private function formatTestNameLine(string $line): string
    {
        return (string) preg_replace(
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
