<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

class LoadMarketingListData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
            'name'          => 'list1',
            'entity'        => 'CB\Bundle\WebsphereBundle\Entity\Subscriber',
            'type'          => 'dynamic',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.marketing_list.first'
        ],
        [
            'name'          => 'list2',
            'entity'        => 'CB\Bundle\WebsphereBundle\Entity\Subscriber',
            'type'          => 'dynamic',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.marketing_list.second'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $listTypeRepository = $manager->getRepository('OroCRMMarketingListBundle:MarketingListType');

        foreach ($this->data as $data) {
            $entity = new MarketingList();
            $data['type']       = $listTypeRepository->find($data['type']);
            $data['owner']      = $admin;
            $this->resolveReferenceIfExist($data, 'organization');
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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUserData',
        ];
    }
}
