<?php

namespace App\Actions;

use App\Data\ScormConfigData;
use App\Data\ScormConfigWithMetadataData;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use ZipArchive;

class BuildArchive
{
    public static function execute(ScormConfigData|ScormConfigWithMetadataData $config): void
    {
        $archive = Str::of($config->destination)
            ->append(DIRECTORY_SEPARATOR)
            ->append($config->packageName)
            ->whenContains(
                needles: ['.zip'],
                callback: function (Stringable $string) {
                    return $string;
                },
                default: function (Stringable $string) {
                    return $string->append('.zip');
                }
            )
            ->value();

        $zip = new ZipArchive;

        throw_if(
            $zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true,
            new Exception('Can not create archive.')
        );

        self::addSourceToArchive($zip, $config->source);

        $zip->close();
    }

    private static function addSourceToArchive(ZipArchive $zipArchive, string $source)
    {
        foreach (File::allFiles($source) as $item) {
            $zipArchive->addFile(
                filepath: $item->getRealPath(),
                entryname: $item->getRelativePathname()
            );
        }
    }
}
