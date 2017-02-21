<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ChangedFieldLogRepository extends EntityRepository
{
    /**
     * @return QueryBuilder
     */
    public function getLogsForProcessingQB()
    {
        $qb = $this->createQueryBuilder('log');
        $qb->where($qb->expr()->isNotNull('log.relatedId'));

        return $qb;
    }

    /**
     * @param int $entityId
     * @param int $logId
     */
    public function addEntityIdToLog($entityId, $logId)
    {
        $qb = $this->createQueryBuilder('log');
        $qb->update()
            ->where('log.id = :logId')
            ->set('log.relatedId', ':entityId')
            ->setParameter('logId', $logId)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->execute();
    }
}
