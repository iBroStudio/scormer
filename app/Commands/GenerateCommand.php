<?php

namespace App\Commands;

use App\Contracts\ScormConfig;
use App\Enums\ScormVersions;
use App\Exceptions\InvalidScormManifestSchemaException;
use App\Exceptions\UnsupportedVersionException;
use App\Scormer;
use Dotenv\Dotenv;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use function Termwind\{render};

class GenerateCommand extends Command
{
    protected $signature = 'generate {name=Artisan}';

    protected $description = 'Generate a scorm package from a static html/js site';

    public function handle(): int
    {
        $this->hero('SCORMER', 'a SCORM package generator by iBroStudio');

        $config = $this->loadConfig();

        if ($config instanceof ScormConfig) {
            try {
                $scormer = App::makeWith(Scormer::class, ['config' => $config]);

                $scormer->generate();

                $this->success('SCORM package successfully generated!');

                if (! $this->hasConfigFile()
                    && $this->confirm(question: 'Save configuration?', default: true)
                ) {
                    $this->saveConfig($config);
                }

            } catch (InvalidScormManifestSchemaException|UnsupportedVersionException $e) {
                $this->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    private function loadConfig(): ScormConfig|null
    {
        if ($this->hasConfigFile()) {
            $config = Dotenv::parse(File::get(getcwd().'/.scorm'));

            $configDataClass = ScormVersions::from($config['version'])->getConfigDataClass();

            return $configDataClass::from(Dotenv::parse(File::get(getcwd().'/.scorm')));
        }

        $config = [
            'version' => $this->choice(
                question: 'Version of SCORM package',
                choices: ScormVersions::options(),
                default: '1.2',
                attempts: null,
                multiple: false
            ),
            'organization' => $organization = Str::squish($this->ask('Name of organization')),
            'title' => $title = Str::squish($this->ask('Title of course')),
            'identifier' => $identifier = Str::squish(
                $this->ask(
                    question: 'Course identifier',
                    default: Str::of($organization)
                        ->append('-')
                        ->append($title)
                        ->slug()
                        ->value()
                )
            ),
            'source' => Str::squish(
                $this->ask(question: 'Source directory')
            ),
            'destination' => Str::squish(
                $this->ask(
                    question: 'Path to directory where course package will be placed',
                    default: 'scorm'
                )
            ),
            'masteryScore' => Str::squish(
                $this->ask(
                    question: 'Score for course passing',
                    default: 80
                )
            ),
            'startingPage' => Str::squish(
                $this->ask(
                    question: 'Page that will open on course start',
                    default: 'index.html'
                )
            ),
            'packageName' => Str::squish(
                $this->ask(
                    question: 'Package filename',
                    default: Str::of($identifier)
                        ->replace('-', '_')
                        ->append('.zip')
                        ->value()
                )
            ),
            'metadataDescription' => Str::squish(
                $this->ask(
                    question: 'Metadata description'
                )
            ),

        ];

        $configDataClass = ScormVersions::from($config['version'])->getConfigDataClass();

        if (Arr::has(get_class_vars($configDataClass), 'entryIdentifier')) {
            $config = [
                ...$config,
                'entryIdentifier' => $this->ask('Metadata entry identifier', 1),
                'catalogValue' => $this->ask('Metadata catalog value', 'Catalog'),
                'lifeCycleVersion' => $this->ask('LifeCycle version of Metadata', 1),
                'classification' => $this->ask('Metadata classification', 'educational objective'),
            ];
        }

        try {
            return $configDataClass::from($config);
        } catch (\TypeError $e) {
            $this->error($e->getMessage());
        }

        return null;
    }

    private function saveConfig(ScormConfig $config): void
    {
        if (File::put(
            path: getcwd().'/.scorm',
            contents: Arr::join(
                collect($config->toArray())
                    ->map(fn ($value, $key) => preg_match('/\s/', $value)
                            ? "$key='$value'"
                            : "$key=$value"
                    )
                    ->toArray(),
                PHP_EOL
            )
        )) {
            $this->success('Configuration saved in .scorm file');
        }
    }

    private function hasConfigFile(): bool
    {
        return File::exists(getcwd().'/.scorm');
    }

    private function hero(string $title, string $description = null): void
    {
        render(<<<HTML
            <div class="py-1 ml-2">
                <div class="px-1 bg-blue-300 text-black">$title</div>
                <em class="ml-1">
                  $description
                </em>
            </div>
        HTML);
    }

    private function success(string $message): void
    {
        render(<<<HTML
            <div class="py-1 ml-2">
                <div class="px-1 bg-green-300 text-black">$message</div>
            </div>
        HTML);
    }
}
