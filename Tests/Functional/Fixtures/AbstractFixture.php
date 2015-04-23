<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

abstract class AbstractFixture extends BaseFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor = false;

    /**
     * Sets $entity object properties from $data array
     *
     * @param object $entity
     * @param array $data
     * @param array $excludeProperties
     */
    public function setEntityPropertyValues($entity, array $data, array $excludeProperties = array())
    {
        foreach ($data as $property => $value) {
            if (in_array($property, $excludeProperties)) {
                continue;
            }
            $this->getPropertyAccessor()->setValue($entity, $property, $value);
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (false === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param string $date
     * @return \DateTime
     */
    protected function convertDate($date)
    {
        return date_create_from_format('Y-m-d', $date);
    }

    /**
     * @param array  $data
     * @param string $name
     */
    protected function resolveReferenceIfExist(array &$data, $name)
    {
        if (!empty($data[$name])) {
            $data[$name] = $this->getReference($data[$name]);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string $enumCode
     * @param mixed $id
     * @return AbstractEnumValue
     */
    protected function findEnum(ObjectManager $manager, $enumCode, $id)
    {
        $enumClass = ExtendHelper::buildEnumValueClassName($enumCode);

        return $manager->getRepository($enumClass)->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
