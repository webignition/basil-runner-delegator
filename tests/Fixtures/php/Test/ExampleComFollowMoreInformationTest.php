<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BaseBasilTestCase\Statement;
use webignition\DomElementIdentifier\ElementIdentifier;

class ExampleComFollowMoreInformationTest extends AbstractBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client->request('GET', 'https://example.com/');
        self::setBasilTestPath('{{ test_path }}');
    }

    public function test0b4535a573cf4196b7e26f0f41e6e2e7()
    {
        $this->setBasilStepName('follow more information');

        // $"a" exists <- click $"a"
        $statement = Statement::createAssertion('$"a" exists');
        $this->currentStatement = $statement;
        $this->examinedValue = $this->navigator->hasOne(ElementIdentifier::fromJson('{
            "locator": "a"
        }'));
        $this->assertTrue(
            $this->examinedValue,
            '{
            "assertion": {
                "source": "$\\"a\\" exists",
                "identifier": "$\\"a\\"",
                "comparison": "exists"
            }
        }'
        );
        $this->completedStatements[] = $statement;

        // click $"a"
        $statement = Statement::createAction('click $"a"');
        $this->currentStatement = $statement;
        $element = $this->navigator->findOne(ElementIdentifier::fromJson('{
            "locator": "a"
        }'));
        $element->click();
        self::$crawler = self::$client->refreshCrawler();
        $this->completedStatements[] = $statement;

        // $page.url is "https://www.iana.org/domains/reserved"
        $statement = Statement::createAssertion('$page.url is "https://www.iana.org/domains/reserved"');
        $this->currentStatement = $statement;
        $this->expectedValue = "https://www.iana.org/domains/reserved" ?? null;
        $this->examinedValue = self::$client->getCurrentURL() ?? null;
        $this->assertEquals(
            $this->expectedValue,
            $this->examinedValue,
            '{
            "assertion": {
                "source": "$page.url is \\"https:\\/\\/www.iana.org\\/domains\\/reserved\\"",
                "identifier": "$page.url",
                "comparison": "is",
                "value": "\\"https:\\/\\/www.iana.org\\/domains\\/reserved\\""
            }
        }'
        );
        $this->completedStatements[] = $statement;
    }
}
