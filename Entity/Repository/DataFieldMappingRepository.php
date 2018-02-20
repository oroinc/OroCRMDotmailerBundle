<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DataFieldMappingRepository extends EntityRepository
{
    /**
     * Returns mapping array for entity configured with two way sync flag, [datafieldName => entityFields]
     *
     * @param string $entityClass
     * @param int $channelId
     * @return array
     */
    public function getTwoWaySyncFieldsForEntity($entityClass, $channelId)
    {
        return $this->getMappingConfigForEntity($entityClass, $channelId, true);
    }

    /**
     * Returns mapping array for entity, [datafieldName => entityFields]
     *
     * @param string $entityClass
     * @param int $channelId
     * @param bool $twoWayOnly
     * @return array
     */
    public function getMappingConfigForEntity($entityClass, $channelId, $twoWayOnly = false)
    {
        $qb = $this->createQueryBuilder('mapping');
        if ($twoWayOnly) {
            $qb
                ->innerJoin('mapping.configs', 'config', Expr\Join::WITH, 'config.isTwoWaySync = :twoWay')
                ->setParameter('twoWay', true);
        } else {
            $qb->innerJoin('mapping.configs', 'config');
        }
        $qb
            ->select('config.entityFields as entityFieldName')
            ->innerJoin('config.dataField', 'dataField')
            ->addSelect('dataField.name as dataFieldName')
            ->where('mapping.channel = :channel')
            ->andWhere('mapping.entity = :entityClass')
            ->setParameter('channel', $channelId)
            ->setParameter('entityClass', $entityClass);

        $result = $qb->getQuery()->getArrayResult();

        if ($result) {
            $result = array_column($result, 'entityFieldName', 'dataFieldName');
        }

        return $result;
    }

    /**
     * Get entity classes which have at least one mapped field with two way sync
     *
     * @param Channel $channel
     * @return array
     */
    public function getEntitiesQualifiedForTwoWaySync(Channel $channel)
    {
        $qb = $this->createQueryBuilder('mapping');
        $qb->select('mapping.entity')
            ->distinct()
            ->innerJoin('mapping.configs', 'config', Expr\Join::WITH, 'config.isTwoWaySync = :twoWay')
            ->where('mapping.channel = :channel')
            ->setParameter('twoWay', true)
            ->setParameter('channel', $channel);

        $result = $qb->getQuery()->getArrayResult();
        if ($result) {
            $result = array_column($result, 'entity');
        }

        return $result;
    }

    /**
     * @param Channel $channel
     * @return array
     */
    public function getMappingBySyncPriority($channel)
    {
        $qb = $this->createQueryBuilder('mapping');
        $qb->innerJoin('mapping.configs', 'config');
        $qb
            ->innerJoin('config.dataField', 'dataField')
            ->addSelect('dataField.name as dataFieldName')
            ->addSelect('mapping.entity')
            ->addSelect('mapping.syncPriority')
            ->where('mapping.channel = :channel')
            ->setParameter('channel', $channel);

        $result = $qb->getQuery()->getArrayResult();

        return $result;
    }
}
