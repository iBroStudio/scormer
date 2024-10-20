<?php

namespace App\Commands;

use App\Contracts\ScormConfig;
use App\Data\ScormConfigData;
use App\Data\ScormConfigWithMetadataData;
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

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\text;

class GenerateCommand extends Command
{
    protected $signature = 'generate';

    protected $description = 'Generate a scorm package from a static html/js site';

    protected $workingDirectory;

    public function handle(): int
    {
        intro('SCORMER by iBroStudio');

        $this->workingDirectory = getcwd().(config('app.env') === 'testing' ? '/project-test' : '');

        $config = $this->loadConfig();

        if ($config instanceof ScormConfigData || $config instanceof ScormConfigWithMetadataData) {
            try {
                $scormer = App::makeWith(Scormer::class, ['config' => $config]);

                $scormer->generate();

                info('SCORM package successfully generated!');

                if (! $this->hasConfigFile()
                    && confirm('Save configuration?')
                ) {
                    $this->saveConfig($config);
                }

            } catch (InvalidScormManifestSchemaException|UnsupportedVersionException $e) {
                error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    private function loadConfig(): ?ScormConfig
    {
        if ($this->hasConfigFile()) {
            $config = Dotenv::parse(File::get($this->workingDirectory.'/.scorm'));

            $configDataClass = ScormVersions::from($config['version'])->getConfigDataClass();

            return $configDataClass::from($config);
        }

        $config = form()
            ->select(
                label: 'Version of SCORM package',
                options: ScormVersions::options(),
                default: '1.2',
                name: 'version'
            )

            ->text(
                label: 'Name of organization',
                required: true,
                name: 'organization'
            )

            ->text(
                label: 'Title of course',
                required: true,
                name: 'title'
            )

            ->add(
                function ($responses) {
                    return text(
                        label: 'Course identifier',
                        default: Str::of($responses['organization'])
                            ->append('-')
                            ->append($responses['title'])
                            ->slug()
                            ->toString(),
                        required: true
                    );
                },
                name: 'identifier'
            )

            ->text(
                label: 'Source directory',
                required: true,
                name: 'source'
            )

            ->text(
                label: 'Path to directory where course package will be placed',
                default: 'scorm',
                required: true,
                name: 'destination'
            )

            ->text(
                label: 'Score for course passing',
                default: '80',
                required: true,
                name: 'masteryScore'
            )

            ->text(
                label: 'Page that will open on course start',
                default: 'index.html',
                required: true,
                name: 'startingPage'
            )

            ->add(
                function ($responses) {
                    return text(
                        label: 'Package filename',
                        default: Str::of($responses['identifier'])
                            ->replace('-', '_')
                            ->append('.zip')
                            ->toString(),
                        required: true
                    );
                },
                name: 'packageName'
            )

            ->text(
                label: 'Metadata description',
                name: 'metadataDescription'
            )

            ->submit();

        $configDataClass = ScormVersions::from($config['version'])->getConfigDataClass();

        if (Arr::has(get_class_vars($configDataClass), 'entryIdentifier')) {

            $additionnal_config = form()
                ->text(
                    label: 'Metadata entry identifier',
                    default: '1',
                    required: true,
                    name: 'entryIdentifier'
                )

                ->text(
                    label: 'Metadata catalog value',
                    default: 'Catalog',
                    required: true,
                    name: 'catalogValue'
                )

                ->text(
                    label: 'LifeCycle version of Metadata',
                    default: '1',
                    required: true,
                    name: 'lifeCycleVersion'
                )

                ->text(
                    label: 'Metadata classification',
                    default: 'educational objective',
                    required: true,
                    name: 'classification'
                )

                ->submit();

            $config = array_merge($config, $additionnal_config);
        }

        try {
            return $configDataClass::from($config);
        } catch (\TypeError $e) {
            error($e->getMessage());
        }

        return null;
    }

    private function saveConfig(ScormConfigData|ScormConfigWithMetadataData $config): void
    {
        if (File::put(
            path: $this->workingDirectory.'/.scorm',
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
            info('Configuration saved in .scorm file');
        }
    }

    private function hasConfigFile(): bool
    {
        return File::exists($this->workingDirectory.'/.scorm');
    }
}
