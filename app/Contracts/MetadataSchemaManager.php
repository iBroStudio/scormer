<?php

namespace App\Contracts;

use App\Data\MetadataSchemaData;

interface MetadataSchemaManager
{
    public static function getMetadataSchema(MetadataSchemaData $data): array;
}
