<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var array */
    protected $data = [
        [
            'username'      => 'john.doe',
            'firstName'     => 'John',
            'lastName'      => 'Doe',
            'email'         => 'john.doe@example.com',
            'plainPassword' => 'password',
            'role'          => 'ROLE_ADMIN',
            'owner'         => 'oro_dotmailer.business_unit.foo',
            'organization'  => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.user.john.doe',
        ],
        [
            'username'      => 'jane.smith',
            'firstName'     => 'Jane',
            'lastName'      => 'Smith',
            'email'         => 'jane.smith@example.com',
            'plainPassword' => 'password',
            'role'          => 'ROLE_MANAGER',
            'owner'         => 'oro_dotmailer.business_unit.foo',
            'organization'  => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.user.jane.smith',
        ],
        [
            'username'      => 'jack.tailor',
            'firstName'     => 'Jane',
            'lastName'      => 'Tailor',
            'email'         => 'jack.tailor@example.com',
            'plainPassword' => 'password',
            'role'          => 'ROLE_USER',
            'owner'         => 'oro_dotmailer.business_unit.foo',
            'organization'  => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.user.jack.tailor',
        ],
        [
            'username'      => 'daniel.wilson',
            'firstName'     => 'Daniel',
            'lastName'      => 'Wilson',
            'email'         => 'daniel.wilson@example.com',
            'plainPassword' => 'password',
            'role'          => 'ROLE_ADMIN',
            'owner'         => 'oro_dotmailer.business_unit.foo',
            'organization'  => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.user.daniel.wilson',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        foreach ($this->data as $data) {
            $entity = $userManager->createUser();

            $this->resolveReferenceIfExist($data, 'owner');
            $this->resolveReferenceIfExist($data, 'organization');
            $this->setEntityPropertyValues($entity, $data, ['reference', 'role']);
            $this->setReference($data['reference'], $entity);

            $this->assignUserRole($manager, $data, $entity);

            if ($entity->getOrganization()) {
                $entity->addOrganization($entity->getOrganization());
            }

            $manager->persist($entity);
            $userManager->updateUser($entity, false);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param array         $data
     * @param User          $user
     */
    protected function assignUserRole(ObjectManager $manager, $data, $user)
    {
        if (isset($data['role'])) {
            $role = $manager->getRepository(Role::class)->findOneByRole($data['role']);
            if ($role) {
                $user->addUserRole($role);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadBusinessUnitData'];
    }
}
