<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
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
     */
    public function loadEnumValues(array $enumData, EntityManagerInterface $manager)
    {
        foreach ($enumData as $enumCode => $enumValues) {
            $entityName = ExtendHelper::buildEnumValueClassName($enumCode);
            /** @var EnumValueRepository $enumRepository */
            $enumRepository = $manager->getRepository($entityName);

            $existingCodes = [];
            $existingValues = $enumRepository->findAll();

            /** @var AbstractEnumValue $existingValue */
            foreach ($existingValues as $existingValue) {
                $existingCodes[$existingValue->getId()] = true;
            }

            $priority = 1;

            foreach ($enumValues as $key => $value) {
                if (!isset($existingCodes[$key])) {
                    /** @var AbstractEnumValue $enum */
                    $enum = $enumRepository->createEnumValue($value, $priority++, false, $key);
                    $existingCodes[$key] = true;
                    $manager->persist($enum);
                }
            }
        }

        $manager->flush();
    }
}
