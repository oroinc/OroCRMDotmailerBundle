<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;

class LoadCampaignData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'originId'      => 15662,
            'name'          => 'NewsLetter',
            'subject'       => 'News Letter',
            'fromName'      => 'CityBeach',
            'fromAddress'   => 'Arbitbet@dotmailer-email.com',
            'reply_action'  => 'Webmail',
            'isSplitTest'   => false,
            'status'        => 'Sent',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'channel'       => 'orocrm_dotmailer.channel.second',
            'emailCampaign' => 'orocrm_dotmailer.email_campaign.first',
            'reference'     => 'orocrm_dotmailer.campaign.first',
        ],
        [
            'originId'      => 15663,
            'name'          => 'Abandoned',
            'subject'       => 'Abandoned Cart',
            'fromName'      => 'CityBeach',
            'fromAddress'   => 'Arbitbet@dotmailer-email.com',
            'reply_action'  => 'Webmail',
            'isSplitTest'   => false,
            'status'        => 'Unsent',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'channel'       => 'orocrm_dotmailer.channel.first',
            'reference'     => 'orocrm_dotmailer.campaign.second'
        ],
        [
            'originId'      => 15664,
            'name'          => 'Test Campaign',
            'subject'       => 'Test Campaign',
            'fromName'      => 'CityBeach',
            'fromAddress'   => 'Arbitbet@dotmailer-email.com',
            'reply_action'  => 'Webmail',
            'isSplitTest'   => false,
            'status'        => 'Unsent',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'channel'       => 'orocrm_dotmailer.channel.first',
            'reference'     => 'orocrm_dotmailer.campaign.third'
        ],
        [
            'originId'      => 15665,
            'name'          => 'Already Deleted',
            'subject'       => 'Already Deleted',
            'fromName'      => 'CityBeach',
            'fromAddress'   => 'Arbitbet@dotmailer-email.com',
            'reply_action'  => 'Webmail',
            'isSplitTest'   => false,
            'deleted'       => true,
            'status'        => 'Sent',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'channel'       => 'orocrm_dotmailer.channel.second',
            'reference'     => 'orocrm_dotmailer.campaign.fourth',
        ],
        [
            'originId'      => 15666,
            'name'          => 'Test Address Book',
            'subject'       => 'Test Address Book',
            'fromName'      => 'CityBeach',
            'fromAddress'   => 'Arbitbet@dotmailer-email.com',
            'reply_action'  => 'Webmail',
            'isSplitTest'   => false,
            'status'        => 'Sent',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'channel'       => 'orocrm_dotmailer.channel.second',
            'emailCampaign' => 'orocrm_dotmailer.email_campaign.second',
            'reference'     => 'orocrm_dotmailer.campaign.fifth',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new Campaign();
            $data['reply_action'] = $this->findEnum('dm_cmp_reply_action', $data['reply_action']);
            $data['status'] = $this->findEnum('dm_cmp_status', $data['status']);
            $this->resolveReferenceIfExist($data, 'emailCampaign');
            $this->resolveReferenceIfExist($data, 'channel');
            $this->resolveReferenceIfExist($data, 'owner');
            $this->setEntityPropertyValues($entity, $data, ['reference']);

            $this->addReference($data['reference'], $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadOrganizationData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadEmailCampaignData',
        ];
    }
}
