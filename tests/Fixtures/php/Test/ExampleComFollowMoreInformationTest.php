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
        $this->handledStatements[] = Statement::createAssertion(
            '{
            "source": "$\\"a\\" exists",
            "identifier": "$\\"a\\"",
            "comparison": "exists"
        }',
            Statement::createAction('{
            "source": "click $\\"a\\"",
            "type": "click",
            "arguments": "$\\"a\\"",
            "identifier": "$\\"a\\""
        }')
        );
        $this->examinedElementIdentifier = ElementIdentifier::fromJson('{
            "locator": "a"
        }');
        $this->examinedValue = $this->navigator->hasOne($this->examinedElementIdentifier);
        $this->assertTrue(
            $this->examinedValue,
            '{
            "assertion": {
                "source": "$\\"a\\" exists",
                "identifier": "$\\"a\\"",
                "comparison": "exists"
            },
            "derived_from": {
                "statement_type": "action",
                "statement": {
                    "source": "click $\\"a\\"",
                    "type": "click",
                    "arguments": "$\\"a\\"",
                    "identifier": "$\\"a\\""
                }
            }
        }'
        );

        // click $"a"
        $this->handledStatements[] = Statement::createAction(
            '{
            "source": "click $\\"a\\"",
            "type": "click",
            "arguments": "$\\"a\\"",
            "identifier": "$\\"a\\""
        }'
        );
        $element = $this->navigator->findOne(ElementIdentifier::fromJson('{
            "locator": "a"
        }'));
        $element->click();
        self::$crawler = self::$client->refreshCrawler();

        // $page.url is "https://www.iana.org/domains/reserved"
        $this->handledStatements[] = Statement::createAssertion(
            '{
            "source": "$page.url is \\"https:\\/\\/www.iana.org\\/domains\\/reserved\\"",
            "identifier": "$page.url",
            "comparison": "is",
            "value": "\\"https:\\/\\/www.iana.org\\/domains\\/reserved\\""
        }'
        );
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
    }
}
