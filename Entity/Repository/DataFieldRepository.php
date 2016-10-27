<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\DataField;

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
            ->where('dataField.channel =:channel');
        $qb->setParameters(['channel' => $channel]);

        if (count($keepDataFieldsNames) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('dataField.name', $keepDataFieldsNames)
            );
        }

        return $qb;
    }
}
