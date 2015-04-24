<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use DotMailer\Api\DataTypes\ApiContactStatuses;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;

class LoadDotmailerContactData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'originId'      => 42,
            'channel'       => 'orocrm_dotmailer.channel.second',
            'email'         => 'first@mail.com',
            'status'        => ApiContactStatuses::SUBSCRIBED,
            'address_books' => ['orocrm_dotmailer.address_book.third'],
            'reference'     => 'orocrm_dotmailer.contact.first',
        ],
        [
            'originId'      => 42,
            'email'         => 'second@mail.com',
            'channel'       => 'orocrm_dotmailer.channel.third',
            'status'        => ApiContactStatuses::SUBSCRIBED,
            'address_books' => ['orocrm_dotmailer.address_book.third', 'orocrm_dotmailer.address_book.fourth'],
            'reference'     => 'orocrm_dotmailer.contact.second',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        foreach ($this->data as $item) {
            $contact = new Contact();
            $contact->setOriginId($item['originId']);
            $contact->setOwner($admin->getOrganization());
            $contact->setChannel($this->getReference($item['channel']));
            $contact->setEmail($item['email']);
            $contact->setStatus(
                $this->findEnum('dm_cnt_status', $item['status'])
            );

            foreach ($item['address_books'] as $addressBook) {
                $contact->addAddressBook($this->getReference($addressBook));
            }

            $manager->persist($contact);

            $this->setReference($item['reference'], $contact);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData',
        ];
    }
}
