<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilValidationResult\InvalidResultInterface;

class ValidatorInvalidResultSerializer
{
    /**
     * @param InvalidResultInterface $invalidResult
     *
     * @return array<mixed>
     */
    public function serializeToArray(InvalidResultInterface $invalidResult): array
    {
        $serializedData = [
            'type' => $invalidResult->getType(),
            'reason' => $invalidResult->getReason(),
        ];

        $context = $invalidResult->getContext();
        if ([] !== $context) {
            $serializedData['context'] = $context;
        }

        $subject = $this->createSerializedSubject($invalidResult);
        if (null !== $subject) {
            $serializedData['subject'] = $subject;
        }

        $previous = $invalidResult->getPrevious();
        if ($previous instanceof InvalidResultInterface) {
            $serializedData['previous'] = $this->serializeToArray($previous);
        }

        return $serializedData;
    }

    private function createSerializedSubject(InvalidResultInterface $invalidResult): ?string
    {
        $subject = $invalidResult->getSubject();

        if (is_scalar($subject)) {
            return (string) json_encode($subject);
        }

        if (is_object($subject)) {
            if (true === method_exists($subject, '__toString')) {
                return (string) json_encode((string) $subject);
            }

            if ($subject instanceof \JsonSerializable) {
                return (string) json_encode($subject);
            }
        }

        return null;
    }
}
