<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DataFieldRepository extends EntityRepository
{
    /**
     * @param Channel $channel
     * @param array   $keepDataFieldsNames
     *
     * @return QueryBuilder
     */
    public function getDataFieldsForRemoveQB(Channel $channel, array $keepDataFieldsNames)
    {
        $qb = $this->createQueryBuilder('dataField');
        $qb->select('dataField.id')
            ->where('dataField.channel =:channel')
            ->addOrderBy('dataField.id');
        $qb->setParameters(['channel' => $channel]);

        if (count($keepDataFieldsNames) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('dataField.name', $keepDataFieldsNames)
            );
        }

        return $qb;
    }

    /**
     * @param array $names
     * @param Channel $channel
     *
     * @return array associative array with dotmailer field name as key and datafield object as a value
     */
    public function getChannelDataFieldByNames(array $names, Channel $channel)
    {
        $qb = $this->createQueryBuilder('dataField');

        $result = $qb
            ->select('dataField')
            ->where($qb->expr()->in('dataField.name', ':names'))
            ->andWhere('dataField.channel =:channel')
            ->setParameters(['channel' => $channel, 'names' => $names])
            ->getQuery()
            ->getResult();
        $map = [];
        /** @var DataField $record */
        foreach ($result as $record) {
            $map[$record->getName()] = $record;
        }

        return $map;
    }
}
