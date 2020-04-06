<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception;

use webignition\BasilAssertionFailureMessage\FailureMessageException;

class AssertionFailureMessageParseException extends \Exception
{
    public const CODE_MALFORMED_FAILURE_MESSAGE = 1;
    public const CODE_MALFORMED_DATA = 2;

    private const MESSAGE_MALFORMED_FAILURE_MESSAGE = 'Malformed failure message';
    private const MESSAGE_MALFORMED_DATA = 'Malformed data';

    private $failureMessage;

    public function __construct(
        string $failureMessage,
        string $message,
        int $code,
        FailureMessageException $failureMessageException = null
    ) {
        $this->failureMessage = $failureMessage;

        parent::__construct($message, $code, $failureMessageException);
    }

    public function getFailureMessage(): string
    {
        return $this->failureMessage;
    }

    public static function createMalformedFailureMessageException(string $failureMessage): self
    {
        return new AssertionFailureMessageParseException(
            $failureMessage,
            self::MESSAGE_MALFORMED_FAILURE_MESSAGE,
            self::CODE_MALFORMED_FAILURE_MESSAGE
        );
    }

    public static function createMalformedDataException(
        string $failureMessage,
        FailureMessageException $failureMessageException
    ): self {
        return new AssertionFailureMessageParseException(
            $failureMessage,
            self::MESSAGE_MALFORMED_DATA,
            self::CODE_MALFORMED_DATA,
            $failureMessageException
        );
    }
}
