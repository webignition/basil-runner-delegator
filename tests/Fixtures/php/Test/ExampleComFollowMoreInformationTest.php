<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;
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
        $this->handledStatements[] = $this->assertionFactory->createFromJson('{
            "source_type": "action",
            "source": {
                "source": "click $\\"a\\"",
                "type": "click",
                "arguments": "$\\"a\\"",
                "identifier": "$\\"a\\""
            },
            "identifier": "$\\"a\\""
        }');
        $this->examinedElementIdentifier = ElementIdentifier::fromJson('{
            "locator": "a"
        }');
        $this->examinedValue = $this->navigator->hasOne($this->examinedElementIdentifier);
        $this->assertTrue(
            $this->examinedValue
        );

        // click $"a"
        $this->handledStatements[] = $this->actionFactory->createFromJson('{
            "source": "click $\\"a\\"",
            "type": "click",
            "arguments": "$\\"a\\"",
            "identifier": "$\\"a\\""
        }');
        $element = $this->navigator->findOne(ElementIdentifier::fromJson('{
            "locator": "a"
        }'));
        $element->click();
        $this->refreshCrawlerAndNavigator();

        // $page.url is "https://www.iana.org/domains/reserved"
        $this->handledStatements[] = $this->assertionFactory->createFromJson('{
            "source": "$page.url is \\"https:\\/\\/www.iana.org\\/domains\\/reserved\\"",
            "identifier": "$page.url",
            "comparison": "is",
            "value": "\\"https:\\/\\/www.iana.org\\/domains\\/reserved\\""
        }');
        $this->expectedValue = "https://www.iana.org/domains/reserved" ?? null;
        $this->examinedValue = self::$client->getCurrentURL() ?? null;
        $this->assertEquals(
            $this->expectedValue,
            $this->examinedValue
        );
    }
}
