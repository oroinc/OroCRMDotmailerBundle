<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DataFieldMappingRepository extends EntityRepository
{
    /**
     * Returns mapping array for entity configured with two way sync flag
     * [['dataFieldName' => 'datafield'], 'entityFieldName' => 'entityfield']]
     *
     * @param string $entityClass
     * @param int $channelId
     * @return array
     */
    public function getTwoWaySyncFieldsForEntity($entityClass, $channelId)
    {
        $qb = $this->createQueryBuilder('mapping')
            ->innerJoin('mapping.configs', 'config', Expr\Join::WITH, 'config.isTwoWaySync = :twoWay')
            ->addSelect('config.entityFields as entityFieldName')
            ->innerJoin('config.dataField', 'dataField')
            ->addSelect('dataField.name as dataFieldName')
            ->where('mapping.channel = :channel')
            ->andWhere('mapping.entity = :entityClass')
            ->setParameter('twoWay', true)
            ->setParameter('channel', $channelId)
            ->setParameter('entityClass', $entityClass);

        return $qb->getQuery()->getArrayResult();
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
}
