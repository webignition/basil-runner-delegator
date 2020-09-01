<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilRunnerDocuments\Test;
use webignition\BasilRunnerDocuments\TestConfiguration;

class TestFactory
{
    public function fromTestManifest(TestManifest $testManifest): Test
    {
        $configuration = $testManifest->getConfiguration();

        return new Test(
            $testManifest->getSource(),
            new TestConfiguration(
                $configuration->getBrowser(),
                $configuration->getUrl()
            )
        );
    }
}
