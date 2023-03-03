<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractFixture extends BaseFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $manager;

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
    public function setEntityPropertyValues($entity, array $data, array $excludeProperties = [])
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
     * @param string $dateFormat
     * @return \DateTime
     */
    protected function convertDate($date, $dateFormat = 'Y-m-d')
    {
        return date_create_from_format($dateFormat, $date);
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
     * @param string $enumCode
     * @param mixed $id
     * @return AbstractEnumValue
     */
    protected function findEnum($enumCode, $id)
    {
        $enumClass = ExtendHelper::buildEnumValueClassName($enumCode);

        return $this->manager->getRepository($enumClass)->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->manager = $container->get('doctrine')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function load(ObjectManager $manager);
}
