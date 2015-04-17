<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

abstract class AbstractEnumFixture extends AbstractFixture
{
    /**
     * [
     *   enum_code => [
     *      value_id => value,
     *      ...,
     *   ],
     *   ...
     * ]
     *
     * @param array                  $enumData
     * @param EntityManagerInterface $manager
     */
    public function loadEnumValues(array $enumData, EntityManagerInterface $manager)
    {
        $isNewInserted = false;
        foreach ($enumData as $enumCode => $enumValues) {
            $entityName = ExtendHelper::buildEnumValueClassName($enumCode);

            $existingCodes = [];
            $existingStatuses = $manager->getRepository($entityName)->findAll();

            /** @var AbstractEnumValue $existingStatus */
            foreach($existingStatuses as $existingStatus) {
                $existingCodes[$existingStatus->getId()] = true;
            }

            foreach ($enumValues as $key => $value) {
                $enumId = ExtendHelper::buildEnumValueId($key);
                if (isset($existingStatuses[$key])) {
                    continue;
                }

                /** @var AbstractEnumValue $enum */
                $enum = new $entityName($enumId, $value);
                $manager->persist($enum);

                $isNewInserted = true;
            }

        }

        if ($isNewInserted) {
            $manager->flush();
        }
    }
}
