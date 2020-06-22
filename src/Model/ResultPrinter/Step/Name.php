<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\Step;

use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\ResultPrinter\Failure;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatusIcon;
use webignition\BasilRunner\Model\ResultPrinter\Success;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Model\TestOutput\Step;

class Name implements RenderableInterface
{
    private StatusIcon $statusIcon;
    private RenderableInterface $nameLine;

    public function __construct(Step $step)
    {
        $status = $step->getStatus();
        $name = $this->createName($step);

        $this->statusIcon = new StatusIcon($status);
        $this->nameLine = Status::SUCCESS === $status
            ? new Success($name)
            : new Failure($name);
    }

    public function render(): string
    {
        return sprintf(
            '%s %s',
            $this->statusIcon->render(),
            $this->nameLine->render()
        );
    }

    private function createName(Step $step): string
    {
        $name = $step->getName();

        $dataSet = $step->getCurrentDataSet();
        if ($dataSet instanceof DataSetInterface) {
            $name .= ': ' . $dataSet->getName();
        }

        return $name;
    }
}
