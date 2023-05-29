<?php

namespace App\Contracts;

use App\Data\ScormSchemaData;

interface ScormSchemaManager
{
    public static function getSchema(ScormSchemaData $data): array;
}
