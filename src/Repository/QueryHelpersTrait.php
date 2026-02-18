<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Doctrine\DBAL\Query\QueryBuilder;
use Generator;
use Torq\PimcoreHelpersBundle\Model\Common\PaginationDto;

/**
 * @method getById(int $id)
 */
trait QueryHelpersTrait
{
    protected function addPagination(QueryBuilder $qb, ?PaginationDto $pagination = null)
    {
        if ($pagination?->page && $pagination?->pageSize) {
            $qb->setFirstResult(($pagination->page - 1) * $pagination->pageSize);
            $qb->setMaxResults($pagination->pageSize);
        }
        return $qb;
    }

    protected function hydrate(QueryBuilder $qb, bool $lazy = false, bool $withTotal = false): array|Generator
    {
        $ids = $qb->executeQuery()->fetchFirstColumn();
        $products = $lazy ? $this->asGenerator($ids) : array_map([$this, 'getById'], $ids);
        if ($withTotal) {
            $count = $this->getTotalCount($qb);
            return [$products, $count];
        } else {
            return $products;
        }
    }

    protected function getTotalCount(QueryBuilder $qb): int
    {
        $maxResults = $qb->getMaxResults();
        $firstResult = $qb->getFirstResult();

        $qb->setMaxResults(null);
        $qb->setFirstResult(0);
        $count = $qb->executeQuery()->rowCount();

        $qb->setMaxResults($maxResults);
        $qb->setFirstResult($firstResult);
        return (int)$count;
    }

    /** @param int[] $ids */
    protected function asGenerator(array $ids): Generator
    {
        foreach ($ids as $id) {
            yield $this->getById($id);
        }
    }
}