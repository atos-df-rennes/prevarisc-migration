<?php

namespace App\ValueObject;

/**
 * Objet de pagination pour les résultats de recherche.
 * Remplace Zend_Paginator dans la migration.
 */
final readonly class SearchPagination
{
    private int $currentPage;

    /**
     * @param array<int|string, mixed> $items
     */
    public function __construct(private array $items, private int $totalCount, int $currentPage, private int $itemsPerPage)
    {
        $this->currentPage = max(1, $currentPage);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getPageCount(): int
    {
        if (0 === $this->itemsPerPage) {
            return 0;
        }

        return (int) ceil($this->totalCount / $this->itemsPerPage);
    }

    /**
     * @return int[]
     */
    public function getPagesInRange(): array
    {
        $pageCount = $this->getPageCount();
        if (0 === $pageCount) {
            return [];
        }

        $rangeWidth = 10;
        $startPage = max(1, $this->currentPage - (int) floor($rangeWidth / 2));
        $endPage = min($pageCount, $startPage + $rangeWidth - 1);

        if ($endPage - $startPage < $rangeWidth - 1) {
            $startPage = max(1, $endPage - $rangeWidth + 1);
        }

        return range($startPage, $endPage);
    }
}
