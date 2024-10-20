<?php

namespace App;

use App\Actions\BuildArchive;
use App\Actions\BuildManifest;
use App\Contracts\ScormSchemaManager;
use App\Data\MetadataSchemaData;
use App\Data\ScormConfigData;
use App\Data\ScormConfigWithMetadataData;
use App\Data\ScormSchemaData;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class Scormer
{
    const XML_MANIFEST_FILE_NAME = 'imsmanifest.xml';

    const XML_METADATA_FILE_NAME = 'metadata.xml';

    const DIRECTORY_FOR_DEFINITION_FILES = 'definitionFiles';

    public function __construct(
        public ScormConfigData|ScormConfigWithMetadataData $config,
        public ScormSchemaManager $scormSchemaManager
    ) {}

    public function generate(): void
    {
        File::ensureDirectoryExists($this->config->destination);

        $this->transformPaths();
        $this->createManifestFile();
        $this->copyDefinitionFiles();
        $this->createMetadataFile();

        BuildArchive::execute($this->config);

        $this->cleanSource();
    }

    private function transformPaths(): void
    {
        $files = File::allFiles($this->config->source);

        foreach ($files as $file) {
            if ($file->getExtension() === 'js') {
                $content = Str::replace(
                    search: [
                        'window.location.href=new URL(i,window.location.href).href',
                        'src:["/',
                    ],
                    replace: [
                        'window.location.href=new URL(i,window.location.href).href+"/index.html"',
                        'src:["../',
                    ],
                    subject: File::get($file->getRealPath())
                );

                $content = Str::replaceMatches('/href:([a-z]{1}),/', function (array $matches) {
                    return "href:'./'+{$matches[1]}+'/index.html',";
                }, $content);

                File::put($file->getRealPath(), $content);
            }

            if ($file->getExtension() === 'html') {
                $content = Str::replace(
                    search: ['src&#34;:&#34;/', '&quot;/_astro'],
                    replace: ['src&#34;:&#34;./', '&quot;../_astro'],
                    subject: File::get($file->getRealPath())
                );

                $dom = new Crawler($content);

                $items = array_unique($dom
                    ->filter('a')
                    ->each(function (Crawler $node): string {
                        return $node->attr('href');
                    }));

                foreach ($items as $item) {
                    $content = Str::replace(
                        search: $item,
                        replace: $item.'index.html',
                        subject: $content
                    );
                }

                File::put($file->getRealPath(), $content);
            }
        }
    }

    private function createManifestFile(): void
    {
        $schema = $this->scormSchemaManager
            ->getSchema(
                new ScormSchemaData(
                    title: $this->config->title,
                    identifier: $this->config->identifier,
                    organization: $this->config->organization,
                    masteryScore: $this->config->masteryScore,
                    startingPage: $this->config->startingPage,
                    pathToDirectory: $this->config->source,
                    metadataDescription: $this->config->metadataDescription
                )
            );

        File::put(
            path: $this->config->source.DIRECTORY_SEPARATOR.self::XML_MANIFEST_FILE_NAME,
            contents: BuildManifest::execute($schema)
        );
    }

    private function copyDefinitionFiles(): void
    {
        File::copyDirectory(
            directory: realpath(
                Str::of(__DIR__)
                    ->append(DIRECTORY_SEPARATOR)
                    ->append('..')
                    ->append(DIRECTORY_SEPARATOR)
                    ->append('definitions')
                    ->append(DIRECTORY_SEPARATOR)
                    ->append($this->config->version->value)
                    ->value()
            ),
            destination: base_path(
                Str::of($this->config->source)
                    ->append(DIRECTORY_SEPARATOR)
                    ->append(self::DIRECTORY_FOR_DEFINITION_FILES)
                    ->value()
            )
        );
    }

    private function createMetadataFile(): void
    {
        if (method_exists($this->scormSchemaManager, 'getMetadataSchema')) {
            $schema = $this->scormSchemaManager
                ->getMetadataSchema(
                    new MetadataSchemaData(
                        title: $this->config->title,
                        entryIdentifier: $this->config->entryIdentifier,
                        catalogValue: $this->config->catalogValue,
                        lifeCycleVersion: $this->config->lifeCycleVersion,
                        classification: $this->config->classification,
                    )
                );

            File::put(
                path: $this->config->source.DIRECTORY_SEPARATOR.self::XML_METADATA_FILE_NAME,
                contents: BuildManifest::execute($schema)
            );
        }
    }

    private function cleanSource(): void
    {
        File::delete(
            Str::of($this->config->source)
                ->append(DIRECTORY_SEPARATOR)
                ->append(self::XML_MANIFEST_FILE_NAME)
                ->value()
        );

        File::delete(
            Str::of($this->config->source)
                ->append(DIRECTORY_SEPARATOR)
                ->append(self::XML_METADATA_FILE_NAME)
                ->value()
        );

        File::deleteDirectory(
            Str::of($this->config->source)
                ->append(DIRECTORY_SEPARATOR)
                ->append(self::DIRECTORY_FOR_DEFINITION_FILES)
                ->value()
        );
    }
}
