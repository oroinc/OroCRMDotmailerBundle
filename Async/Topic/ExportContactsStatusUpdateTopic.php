<?php

namespace Oro\Bundle\DotmailerBundle\Async\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to load export statuses from Dotmailer
 */
class ExportContactsStatusUpdateTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.dotmailer.export_contacts_status_update';
    }

    public static function getDescription(): string
    {
        return 'Loads export statuses from Dotmailer.';
    }

    public function getDefaultPriority(string $queueName): string
    {
        return MessagePriority::VERY_LOW;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('integrationId')
            ->setAllowedTypes('integrationId', 'int');
    }

    public function createJobName($messageBody): string
    {
        return 'oro_dotmailer:export_contacts_status_update:' . $messageBody['integrationId'];
    }
}
