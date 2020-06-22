<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\Step;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\DataSet\DataSet;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\ResultPrinter\Step\Name;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class NameTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Name $stepName, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $stepName->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'success' => [
                'stepName' => new Name($this->createBasilTestCase('success step name', Status::SUCCESS)),
                'expectedRenderedString' => '<icon-success /> <success>success step name</success>',
            ],
            'failure' => [
                'stepName' => new Name($this->createBasilTestCase('failure step name', Status::FAILURE)),
                'expectedRenderedString' => '<icon-failure /> <failure>failure step name</failure>',
            ],
            'success with data set' => [
                'stepName' => new Name($this->createBasilTestCase(
                    'success step name',
                    Status::SUCCESS,
                    new DataSet('data set name', [])
                )),
                'expectedRenderedString' => '<icon-success /> <success>success step name: data set name</success>',
            ],
        ];
    }

    private function createBasilTestCase(
        string $name,
        int $status,
        ?DataSetInterface $currentDataSet = null
    ): BasilTestCaseInterface {
        $step = \Mockery::mock(BasilTestCaseInterface::class);

        $step
            ->shouldReceive('getBasilStepName')
            ->andReturn($name);

        $step
            ->shouldReceive('getStatus')
            ->andReturn($status);

        $step
            ->shouldReceive('getCurrentDataSet')
            ->andReturn($currentDataSet);

        return $step;
    }
}
