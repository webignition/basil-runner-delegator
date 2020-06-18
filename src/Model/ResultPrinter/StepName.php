<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Model\TestOutput\Step;

class StepName implements RenderableInterface
{
    use IndentTrait;

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
            '%s%s %s',
            $this->createIndentContent(1),
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
