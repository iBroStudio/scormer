<?php

namespace App\SchemaManagers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class AbstractScormSchemaManager
{
    protected static function getSchemaIdentifier(string $title): string
    {
        return Str::replace(' ', '.', $title);
    }

    protected static function getItemIdentifier(string $identifier): string
    {
        return Str::of('item_')
            ->append(
                Str::replace(' ', '', $identifier)
            )
            ->value();
    }

    protected static function getIdentifierRef(string $identifier): string
    {
        return Str::of('resource_')
            ->append(
                Str::replace(' ', '', $identifier)
            )
            ->value();
    }

    protected static function getSchemaOrganization(string $organization): string
    {
        return Str::replace(' ', '_', $organization);
    }

    protected static function getFiles(string $directory): array
    {
        $filesForSchema = [];

        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $filesForSchema[] = [
                'name' => 'file',
                'attributes' => [
                    'href' => $file->getRelativePathname(),
                ],
            ];

        }

        return $filesForSchema;
    }
}
