<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class MetadataSchemaData extends Data
{
    public function __construct(
        public string $title,
        public string $entryIdentifier,
        public string $catalogValue,
        public string $lifeCycleVersion,
        public string $classification
    ) {
    }
}
