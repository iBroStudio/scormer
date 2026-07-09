<?php

namespace App;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class Html
{
    public function __construct(
        public readonly string $source
    ) {}

    public function generate(): void
    {
        File::ensureDirectoryExists($this->source);

        $this->transformPaths();
    }

    private function transformPaths(): void
    {
        $files = File::allFiles($this->source);

        foreach ($files as $file) {

            if ($file->getPath() === '/Volumes/LaCie/dev/zero/scormer/playground/html/dist/fr' && $file->getFilename() === 'index.html') {

            }

            $level = substr_count(
                Str::of($file->getPath())
                    ->chopStart($this->source)
                    ->value(),
                '/'
            );
/*
 * i18nextBrowser.js loadPath:
 * sounds.js src:"
 * configStore.js src:"
 * shoot-to.../index.html before-hydration-url
 */
            $path_prefix = match(true) {
                $level < 1 => './',
                default => Str::of('../')->repeat($level)->value(),
            };

            if ($file->getExtension() === 'js') {
                $content = Str::replace(
                    search: [
                        //'window.location.href=new URL(i,window.location.href).href',
                        'Howl({src:["/',
                        'src:["/',
                        'src:"/',
                        'loadPath:"/',
                    ],
                    replace: [
                        //'window.location.href=new URL(i,window.location.href).href+"/index.html"',
                        'Howl({src:["' . $path_prefix, // with lang
//                        'Howl({src:["../../',
                        'src:["'. $path_prefix,
                        'src:"'. $path_prefix. '../../',
                        'loadPath:"'.$path_prefix . '../',
                    ],
                    subject: File::get($file->getRealPath())
                );
/*
                $content = Str::replaceMatches('/window\.location\.href=new URL\([a-z]{1},window\.location\.href\)\.href/', function (array $matches) {
                    return "{$matches[0]}+'/index.html'";
                }, $content);
*/
                $content = Str::replaceMatches('/href:([a-z]{1}),/', function (array $matches) use ($path_prefix) {
                    return "href:'{$path_prefix}'+{$matches[1]},";
                    //return "href:'./'+{$matches[1]}+'/index.html',";
                }, $content);

                $content = Str::replaceMatches('/addEventListener\("click",function\(\)\{([a-zA-Z]+)\("\/([a-z0-9\-]+)"/', function (array $matches) use ($path_prefix) {
                    return 'addEventListener("click",function(){'.$matches[1].'("'.$path_prefix.$matches[2].'"';
                    //return 'addEventListener("click",function(){'.$matches[1].'("../'.$matches[2].'/index.html"';
                }, $content);

                $content = Str::replaceMatches('/window.location.replace\("\/([a-z0-9\-]+)"/', function (array $matches) use ($path_prefix) {
                    return 'window.location.replace("'.$path_prefix.$matches[1].'"';
                    //return 'window.location.replace("./'.$matches[1].'/index.html"';
                }, $content);

                File::put($file->getRealPath(), $content);
            }

            if ($file->getExtension() === 'html') {
                $content = Str::replace(
                    search: [
                        'src&#34;:&#34;/',
                        '&quot;/_astro',
                        '&quot;/images',
                        '"/_astro',
                        //'<meta http-equiv="refresh" content="2;url=/en/">',
                        'e.value=`/${e.dataset.currentLocale}/`'
                    ],
                    replace: [
                        'src&#34;:&#34;' . $path_prefix,
                        '&quot;'.$path_prefix.'_astro',
                        Str::contains($file->getRelativePath(), '/') ? '&quot;../../images' : '&quot;../images',
                        '"'.$path_prefix.'_astro',
                        //'<meta http-equiv="refresh" content="2;url=./en/index.html">',
                        'e.value=`' . $path_prefix . '${e.dataset.currentLocale}/index.html`'
                    ],
                    subject: File::get($file->getRealPath())
                );

                $dom = new Crawler($content);

                $items = array_unique($dom
                    ->filter('a')
                    ->each(function (Crawler $node): string {
                        return $node->attr('href');
                    }));
/*
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
                            replace: "href=\"{$item}/index.html\"",
                            subject: $content
                        );
                    }
                }
*/
                $sub = Str::contains($content, 'data-level');

                if (Str::contains($content, 'data-lang')) {
                    $content = Str::of($content)
                        ->replaceMatches('/<option data-lang="[a-z]{2}" value="\/([a-z]{2})\/">/', function (array $matches) use ($path_prefix) {
                            return '<option data-lang="'.$matches[1].'" value="'.$path_prefix.$matches[1].'">';
                            /*
                                ? '<option data-lang="'.$matches[1].'" value="../../'.$matches[1].'/index.html">'
                                : '<option data-lang="'.$matches[1].'" value="./'.$matches[1].'/index.html">';
                            */
                        })
                        ->value();
                }

                if (Str::contains($content, 'data-exit-url')) {
                    $content = Str::of($content)
                        ->replaceMatches('/data\-exit([a-z\-]+)?\-url="\/([a-z0-9\-]+)"/', function (array $matches) use ($path_prefix) {
                            return 'data-exit'.$matches[1].'-url="'.$path_prefix.$matches[2].'"';
                            //return 'data-exit'.$matches[1].'-url="../../'.$matches[2].'/index.html"';
                        })
                        ->value();

                    $content = Str::of($content)
                        ->replaceMatches('/data\-exit([a-z\-]+)?\-url="\/([a-z]{2})\/([a-z0-9\-]+)([a-zA-Z0-9\-#]+)?"/', function (array $matches) use ($path_prefix) {
                            return 'data-exit'.$matches[1].'-url="'.$path_prefix.$matches[2].'/'.$matches[3].($matches[4] ?? '').'"';
                            //return 'data-exit'.$matches[1].'-url="../../../'.$matches[2].'/'.$matches[3].'/index.html'.($matches[4] ?? '').'"';
                        })
                        ->value();
                }

                if (Str::contains($content, 'data-url')) {
                    $content = Str::of($content)
                        ->replaceMatches('/data\-url="\/([a-z]{2})\/([a-z0-9\-]+)"/', function (array $matches) use ($path_prefix) {

                            return 'data-url="'.$path_prefix.$matches[1].'/'.$matches[2].'"';
                            //return 'data-url="../../'.$matches[1].'/'.$matches[2].'/index.html"';
                        })
                        ->value();
                }

                File::put($file->getRealPath(), $content);
            }
        }
    }
}

