<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ScormSchemaData extends Data
{
    public function __construct(
        public string $title,
        public string $identifier,
        public string $organization,
        public int $masteryScore,
        public string $startingPage,
        public string $pathToDirectory,
        public ?string $metadataDescription
    ) {}
}
