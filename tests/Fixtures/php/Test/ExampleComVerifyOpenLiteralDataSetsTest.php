<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilModels\DataSet\DataSet;

class ExampleComVerifyOpenLiteralDataSetsTest extends AbstractBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client->request('GET', 'https://example.com/');
        self::setBasilTestPath('/home/jon/www/lib/webignition/basil-runner/tests/Fixtures/basil/Test/example.com.verify-open-literal-data-sets.yml');
    }

    /**
     * @dataProvider dataProvider1
     *
     * @param string $pattern
     */
    public function test1($pattern)
    {
        $this->setBasilStepName('verify page is open');
        $this->setCurrentDataSet(DataSet::fromArray([
            'name' => $this->dataName(),
            'data' => [
                'pattern' => $pattern,
            ],
        ]));

        // $data.pattern is-regexp <- $page.url matches $data.pattern
        $this->handledStatements[] = $this->assertionFactory->createFromJson('{
            "container": {
                "type": "derived-value-operation-assertion",
                "value": "$data.pattern",
                "operator": "is-regexp"
            },
            "statement": {
                "statement-type": "assertion",
                "source": "$page.url matches $data.pattern",
                "identifier": "$page.url",
                "operator": "matches",
                "value": "$data.pattern"
            }
        }');
        $this->setExaminedValue($pattern ?? null);
        $this->setBooleanExpectedValue(
            @preg_match($this->getExaminedValue(), null) === false
        );
        $this->assertFalse(
            $this->getBooleanExpectedValue()
        );

        // $page.url matches $data.pattern
        $this->handledStatements[] = $this->assertionFactory->createFromJson('{
            "statement-type": "assertion",
            "source": "$page.url matches $data.pattern",
            "identifier": "$page.url",
            "operator": "matches",
            "value": "$data.pattern"
        }');
        $this->setExpectedValue($pattern ?? null);
        $this->setExaminedValue(self::$client->getCurrentURL() ?? null);
        $this->assertMatchesRegularExpression(
            $this->getExpectedValue(),
            $this->getExaminedValue()
        );

        // $page.title is "Example Domain"
        $this->handledStatements[] = $this->assertionFactory->createFromJson('{
            "statement-type": "assertion",
            "source": "$page.title is \\"Example Domain\\"",
            "identifier": "$page.title",
            "operator": "is",
            "value": "\\"Example Domain\\""
        }');
        $this->setExpectedValue("Example Domain" ?? null);
        $this->setExaminedValue(self::$client->getTitle() ?? null);
        $this->assertEquals(
            $this->getExpectedValue(),
            $this->getExaminedValue()
        );
    }

    public function dataProvider1(): array
    {
        return [
            '0' => [
                'pattern' => '/example/',
            ],
            '1' => [
                'pattern' => '/\\.com/',
            ],
            '2' => [
                'pattern' => '/^https:\\/\\//',
            ],
        ];
    }
}
