<?php

namespace App\Commands;

use App\Contracts\ScormConfig;
use App\Data\ScormConfigData;
use App\Data\ScormConfigWithMetadataData;
use App\Enums\ScormVersions;
use App\Exceptions\InvalidScormManifestSchemaException;
use App\Exceptions\UnsupportedVersionException;
use App\Html;
use App\Scormer;
use Dotenv\Dotenv;
use IBroStudio\DataRepository\ValueObjects\DependenciesJsonFile;
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

class GenerateHtmlCommand extends Command
{
    protected $signature = 'html';

    protected $description = 'Generate a a static html/js site';

    public function handle(): int
    {
        intro('SCORMER by iBroStudio');

        $html = new Html(getcwd().'/playground/html/dist');

        $html->generate();

        info('HTML successfully generated!');

        return Command::SUCCESS;
    }
}
