<?php

namespace App\Services\Access\Data;

final readonly class AccessCollectionData
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public array $items,
        public array $meta = [],
    ) {}
}
