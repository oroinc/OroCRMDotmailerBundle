<?php

namespace Oro\Bundle\DotmailerBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

/**
 * The base class for autocomplete handlers to search dotmailer channel aware entities.
 */
class ChannelAwareSearchHandler extends SearchHandler
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
     * @param string $searchTerm
     * @param string $channelId
     * @param int    $firstResult
     * @param int    $maxResults
     * @return QueryBuilder
     */
    protected function prepareQueryBuilder($searchTerm, $channelId, $firstResult, $maxResults)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('e');
        $queryBuilder
            ->where($queryBuilder->expr()->like('LOWER(e.name)', ':searchTerm'))
            ->andWhere('e.channel = :channel')
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%')
            ->setParameter('channel', (int)$channelId)
            ->addOrderBy('e.name', 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        list($searchTerm, $channelId) = explode(';', $search);
        $queryBuilder = $this->prepareQueryBuilder($searchTerm, $channelId, $firstResult, $maxResults);
        $query = $this->aclHelper->apply($queryBuilder);

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
