<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception;

use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;

class GenerateCommandValidationException extends \Exception
{
    private $validationResult;

    public function __construct(GenerateCommandValidationResult $validationResult)
    {
        parent::__construct();

        $this->validationResult = $validationResult;
    }

    public function getValidationResult(): GenerateCommandValidationResult
    {
        return $this->validationResult;
    }
}
