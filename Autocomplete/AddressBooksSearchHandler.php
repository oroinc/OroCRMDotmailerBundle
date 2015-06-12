<?php

namespace OroCRM\Bundle\DotmailerBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class AddressBooksSearchHandler extends SearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        list($searchTerm, $channelId, $marketingListId) = explode(';', $search);

        $queryBuilder = $this->entityRepository->createQueryBuilder('e');
        $queryBuilder
            ->where($queryBuilder->expr()->like('LOWER(e.name)', ':searchTerm'))
            ->andWhere('e.channel = :channel')
            ->andWhere('e.marketingList IS NULL or e.marketingList =:marketingList')
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%')
            ->setParameter('channel', (int)$channelId)
            ->setParameter('marketingList', (int)$marketingListId)
            ->addOrderBy('e.name', 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getResult();
    }


    /**
     * {@inheritdoc}
     */
    protected function findById($query)
    {
        $parts = explode(';', $query);
        $id = $parts[0];
        $channelId = !empty($parts[1]) ? $parts[1] : false;

        $criteria = [$this->idFieldName => $id];
        if (false !== $channelId) {
            $criteria['channel'] = $channelId;
        }

        return [$this->entityRepository->findOneBy($criteria, null)];
    }
}
