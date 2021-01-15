<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;

class LoadUpdatesLogData extends AbstractFixture implements DependentFixtureInterface
{
    protected $data = [
        [
            'parentEntity'     => Contact::class,
            'relatedFieldPath' => 'firstName',
            'channel'          => 'oro_dotmailer.channel.first',
            'contact'          => 'oro_dotmailer.orocrm_contact.john.doe',
            'reference'        => 'oro_dotmailer.changed_field_log.first',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $log = new ChangedFieldLog();
            $data['channelId'] = $this->getReference($data['channel'])->getId();
            $data['relatedId'] = $this->getReference($data['contact'])->getId();
            $this->resolveReferenceIfExist($data, 'organization');
            $this->setEntityPropertyValues($log, $data, ['reference', 'contact', 'channel']);

            $this->addReference($data['reference'], $log);
            $manager->persist($log);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
        ];
    }
}
