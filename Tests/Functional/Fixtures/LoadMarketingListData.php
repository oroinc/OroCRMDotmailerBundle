<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListType;

class LoadMarketingListData extends AbstractFixture implements ContainerAwareInterface
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
            'name'          => 'first marketing list',
            'reference'     => 'orocrm_dotmailer.marketing_list.first'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $type = $this->container->get('doctrine')
            ->getRepository('OroCRMMarketingListBundle:MarketingListType')->find(MarketingListType::TYPE_STATIC);

        foreach ($this->data as $item) {
            $marketingList = new MarketingList();
            $marketingList->setOwner($admin);
            $marketingList->setName($item['name']);
            $marketingList->setEntity($this->container->getParameter('orocrm_contact.entity.class'));
            $marketingList->setType($type);

            $manager->persist($marketingList);

            $this->setReference($item['reference'], $marketingList);
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
}
