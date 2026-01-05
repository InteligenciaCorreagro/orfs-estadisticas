<?php
// src/Utils/Paginator.php

namespace App\Utils;

class Paginator
{
    private array $items;
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;
    
    public function __construct(array $items, int $total, int $perPage = 20, int $currentPage = 1)
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = max(1, $currentPage);
        $this->totalPages = (int) ceil($total / $perPage);
    }
    
    public static function paginate(array $allItems, int $perPage = 20, int $page = 1): self
    {
        $total = count($allItems);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($allItems, $offset, $perPage);
        
        return new self($items, $total, $perPage, $page);
    }
    
    public function getItems(): array
    {
        return $this->items;
    }
    
    public function getTotal(): int
    {
        return $this->total;
    }
    
    public function getPerPage(): int
    {
        return $this->perPage;
    }
    
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }
    
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }
    
    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }
    
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }
    
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }
    
    public function getPreviousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }
    
    public function getNextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }
    
    public function getFirstItem(): int
    {
        return ($this->currentPage - 1) * $this->perPage + 1;
    }
    
    public function getLastItem(): int
    {
        return min($this->currentPage * $this->perPage, $this->total);
    }
    
    public function toArray(): array
    {
        return [
            'data' => $this->items,
            'pagination' => [
                'total' => $this->total,
                'per_page' => $this->perPage,
                'current_page' => $this->currentPage,
                'total_pages' => $this->totalPages,
                'has_previous' => $this->hasPreviousPage(),
                'has_next' => $this->hasNextPage(),
                'previous_page' => $this->getPreviousPage(),
                'next_page' => $this->getNextPage(),
                'first_item' => $this->getFirstItem(),
                'last_item' => $this->getLastItem()
            ]
        ];
    }
}