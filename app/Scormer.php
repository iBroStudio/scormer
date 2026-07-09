<?php

namespace App;

use App\Actions\BuildArchive;
use App\Actions\BuildManifest;
use App\Contracts\ScormSchemaManager;
use App\Data\MetadataSchemaData;
use App\Data\ScormConfigData;
use App\Data\ScormConfigWithMetadataData;
use App\Data\ScormSchemaData;
use Illuminate\Support\Arr;
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
                        'Howl({src:["/',
                        'src:["/',
                        'src:"/',
                        'new Audio("/',
                    ],
                    replace: [
                        'window.location.href=new URL(i,window.location.href).href+"/index.html"',
                        'Howl({src:["../../../', // with lang
                       //'Howl({src:["../../', // SUZUKI SlidingDoors.js + Slider.astro_astro_type_script_index_0_lang... => .Howl({src:["../assets
                        'src:["../',
                        'src:"../../../',
                        'new Audio("../../',
                    ],
                    subject: File::get($file->getRealPath())
                );

                $content = Str::replaceMatches('/window\.location\.href=new URL\([a-z]{1},window\.location\.href\)\.href/', function (array $matches) {
                    return "{$matches[0]}+'/index.html'";
                }, $content);

                $content = Str::replaceMatches('/href:([a-z]{1}),/', function (array $matches) {
                    return "href:'./'+{$matches[1]}+'/index.html',";
                }, $content);

                $content = Str::replaceMatches('/addEventListener\("click",function\(\)\{([a-zA-Z]+)\("\/([a-z0-9\-]+)"/', function (array $matches) {
                    return 'addEventListener("click",function(){'.$matches[1].'("../'.$matches[2].'/index.html"';
                }, $content);

                $content = Str::replaceMatches('/window.location.replace\("\/([a-z0-9\-]+)"/', function (array $matches) {
                    return 'window.location.replace("./'.$matches[1].'/index.html"';
                }, $content);

                File::put($file->getRealPath(), $content);
            }

            if ($file->getExtension() === 'html') {

                $content = File::get($file->getRealPath());

                $sub = (int) Str::match('/data-level="(.*)"/', $content);

                $content = Str::replace(
                    search: [
                        'src&#34;:&#34;/',
                        '&quot;/_astro',
                        '&quot;/images',
                        '<!--<script src="../scripts/scormRTE.js"></script>-->',
                        '<meta http-equiv="refresh" content="2;url=/en/">',
                        'e.value=`./${e.dataset.currentLocale}/`',
                        'new Audio("/',
                        'before-hydration-url="/',  // pas pour SUZUKI
                    ],
                    replace: [
                        //'src&#34;:&#34;../../', // PAS VCA Flora
                        //$sub ? 'src&#34;:&#34;../../' : 'src&#34;:&#34;./', // PAS VCA Flora
                        $sub ? 'src&#34;:&#34;../' : 'src&#34;:&#34;./', // VCA Flora
                        '&quot;../../../_astro',
                        Str::contains($file->getRelativePath(), '/') ? '&quot;../../images' : '&quot;../images',
                        '<script src="../scripts/scormRTE.js"></script>',
                        '<meta http-equiv="refresh" content="2;url=./en/index.html">',
                        'e.value=`./${e.dataset.currentLocale}/index.html`',
                        'new Audio("../../../',
                        'before-hydration-url="../../../', // pas pour SUZUKI
                    ],
                    subject: $content
                );

                $dom = new Crawler($content);

                $items = array_unique($dom
                    ->filter('a')
                    ->each(function (Crawler $node): string {
                        return $node->attr('href');
                    }));

                foreach ($items as $item) {
                    if (Str::contains($item, '#')) {
                        $content = Str::replace(
                            search: "href=\"{$item}\"",
                            replace: 'href="' . Str::before($item, '#') . '/index.html#' . Str::after($item, '#') . '"',
                            subject: $content
                        );
                    } else {
                        $content = Str::replace(
                            search: "href=\"{$item}\"",
                            replace: 'href="' . Str::chopEnd($item, '/') . '/index.html"',
                            subject: $content
                        );
                    }
                }

                if (Str::contains($content, 'data-lang')) {
                    $content = Str::of($content)
                        ->replaceMatches('/<option data-lang="[a-z]{2}" value="\/([a-z]{2})\/">/', function (array $matches) use ($sub) {
                            return match ($sub) {
                                2 => '<option data-lang="'.$matches[1].'" value="../../'.$matches[1].'/index.html">',
                                1 => '<option data-lang="'.$matches[1].'" value="../'.$matches[1].'/index.html">',
                                default => '<option data-lang="'.$matches[1].'" value="./'.$matches[1].'/index.html">',
                            };
                        })
                        ->value();
                }

                /*
                if ($file->getRealPath() === '/Volumes/LaCie/dev/zero/scormer/playground/dist/cn/games/shoot-to-reveal/index.html') {
                    if (Str::contains($content, 'data-exit-url')) {
                        preg_match('/data\-exit([a-z\-]+)?\-url="(.*)"/', $content, $matches);
                        //preg_match('/data\-exit([a-z\-]+)?\-url="\/([a-z]{2})\/([a-z0-9\-]+)\/?([a-zA-Z0-9\-#]+)?"/', $content, $matches);
                        dd($matches); //data-exit-url="/cn/chapter2-2/#backFromShootToReveal"
                    }
                }
                */

                if (Str::contains($content, 'data-exit-url')) {
                    $content = Str::of($content)
                        ->replaceMatches('/data\-exit([a-z\-]+)?\-url="\/([a-z0-9\-]+)\/?"/', function (array $matches) use ($sub) {
                            //return 'data-exit'.$matches[1].'-url="../../'.$matches[2].'/index.html"';
                            return match ($sub) {
                                2 => 'data-exit'.$matches[1].'-url="../../'.$matches[2].'/index.html"',
                                1 => 'data-exit'.$matches[1].'-url="../'.$matches[2].'/index.html"',
                                default => 'data-exit'.$matches[1].'-url="../../'.$matches[2].'/index.html"',
                            };
                        })
                        ->value();

                    $content = Str::of($content)
                        ->replaceMatches('/data\-exit([a-z\-]+)?\-url="\/([a-z]{2})\/([a-z0-9\-]+)\/?([a-zA-Z0-9\-#]+)?"/', function (array $matches) {
                            return 'data-exit'.$matches[1].'-url="../../../'.$matches[2].'/'.$matches[3].'/index.html'.($matches[4] ?? '').'"';
                        })
                        ->value();
                }

                if (Str::contains($content, 'data-url')) {
                    $content = Str::of($content)
                        ->replaceMatches('/data\-url="\/([a-z]{2})\/([a-z0-9\-]+)\/?"/', function (array $matches) use ($sub) {
                            return match ($sub) {
                                2 => 'data-url="../../'.$matches[1].'/'.$matches[2].'/index.html"',
                                1 => 'data-url="../'.$matches[1].'/'.$matches[2].'/index.html"',
                                default => 'data-url="./'.$matches[1].'/'.$matches[2].'/index.html"',
                            };
                        })
                        ->value();
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


