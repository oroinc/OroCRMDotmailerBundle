<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DotmailerBundle\Async\Topic\ExportContactsStatusUpdateTopic;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ExportContactsStatusUpdateTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ExportContactsStatusUpdateTopic();
    }

    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'integrationId' => 1,
        ];

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => $requiredOptionsSet,
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "integrationId" is missing./',
            ],
            'wrong integrationId type' => [
                'body' => [
                    'integrationId' => '1',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "integrationId" with value "1" is expected to be of type "int"/',
            ],
        ];
    }

    public function testDefaultPriority(): void
    {
        self::assertEquals(MessagePriority::VERY_LOW, $this->getTopic()->getDefaultPriority('queueName'));
    }
}
