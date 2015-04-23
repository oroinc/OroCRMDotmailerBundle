<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class LoadAddressBookData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $data = [
        [
            'name'          => 'first address book',
            'originid'      => 1234,
            'channel'       => 'orocrm_dotmailer.channel.first',
            'marketingList' => 'orocrm_dotmailer.marketing_list.first',
            'reference'     => 'orocrm_dotmailer.address_book.first'
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
            $addressBook = new AddressBook();
            $addressBook->setOwner($admin->getOrganization());
            $addressBook->setChannel($this->getReference($item['channel']));
            $addressBook->setOriginId($item['originId']);
            $addressBook->setMarketingList($this->getReference($item['marketingList']));
            $addressBook->setName($item['name']);

            $manager->persist($addressBook);

            $this->setReference($item['reference'], $addressBook);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
        ];
    }
}
