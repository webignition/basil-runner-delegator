<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilCompiler\ExternalVariableIdentifiers;

class ExternalVariableIdentifiersFactory
{
    public static function create(): ExternalVariableIdentifiers
    {
        return new ExternalVariableIdentifiers(
            '$this->navigator',
            '$_ENV',
            'self::$client',
            'self::$crawler',
            '$this',
            'self::$inspector',
            'self::$mutator'
        );
    }
}
