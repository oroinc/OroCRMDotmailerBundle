<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture as BaseAbstractEnumFixture;

/**
 * Base enum option fixture.
 */
abstract class AbstractEnumFixture extends BaseAbstractEnumFixture
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
            /** @var EnumOptionRepository $enumRepository */
            $enumRepository = $manager->getRepository(EnumOption::class);

            $existingCodes = [];
            $existingValues = $enumRepository->findBy(['enumCode' => $enumCode]);
            /** @var EnumOptionInterface $existingValue */
            foreach ($existingValues as $existingValue) {
                $existingCodes[$existingValue->getInternalId()] = true;
            }
            $priority = 1;
            foreach ($enumValues as $id => $name) {
                if (isset($existingCodes[$id])) {
                    continue;
                }
                $enumOption = $enumRepository->createEnumOption($enumCode, $id, $name, $priority++);

                $manager->persist($enumOption);
            }
        }

        $manager->flush();
    }

    #[\Override]
    protected function getData(): array
    {
        // method is not used
        return [];
    }

    #[\Override]
    protected function getEnumCode(): string
    {
        // method is not used
        return '';
    }
}
