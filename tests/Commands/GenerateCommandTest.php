<?php

use App\Commands\GenerateCommand;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function Pest\Laravel\artisan;

it('inspires artisans', function () {
    $config = getcwd().'/project-test/.scorm';
    $package = getcwd().'/project-test/scorm/van_cleef_arpels_grand_module_perlee.zip';

    File::delete($config);
    File::delete($package);

    Prompt::fake([
        Key::ENTER, // scorm version: 1.2,
        'V', 'a', 'n', ' ', 'C', 'l', 'e', 'e', 'f', ' ', '&', ' ', 'A', 'r', 'p', 'e', 'l', 's', Key::ENTER, // organization,
        'G', 'r', 'a', 'n', 'd', ' ', 'M', 'o', 'd', 'u', 'l', 'e', ' ', 'P', 'e', 'r', 'l', 'é', 'e', Key::ENTER, // title,
        Key::ENTER, // identifier
        'p', 'r', 'o', 'j', 'e', 'c', 't', '-', 't', 'e', 's', 't', '/', 'd', 'i', 's', 't', Key::ENTER, // source directory
        Key::BACKSPACE, Key::BACKSPACE, Key::BACKSPACE, Key::BACKSPACE, Key::BACKSPACE,
        'p', 'r', 'o', 'j', 'e', 'c', 't', '-', 't', 'e', 's', 't', '/', 's', 'c', 'o', 'r', 'm', Key::ENTER, // destination directory: scorm
        Key::ENTER, // masteryScore: 80
        Key::ENTER, // startingPage: index.html
        Key::ENTER, // packageName: van_cleef_arpels_grand_module_perlee.zip
        Key::ENTER, // metadataDescription: null
        Key::ENTER, // Save configuration: true
    ]);

    artisan(GenerateCommand::class)
        ->expectsOutputToContain('SCORMER by iBroStudio')
        ->expectsOutputToContain('Version of SCORM package')
        ->expectsOutputToContain('Name of organization')
        ->expectsOutputToContain('Title of course')
        ->expectsOutputToContain('Course identifier')
        ->expectsOutputToContain('Source directory')
        ->expectsOutputToContain('Path to directory where course package will be placed')
        ->expectsOutputToContain('Score for course passing')
        ->expectsOutputToContain('Page that will open on course start')
        ->expectsOutputToContain('Package filename')
        ->expectsOutputToContain('Metadata description')
        ->expectsOutputToContain('SCORM package successfully generated!')
        ->expectsOutputToContain('Save configuration?')
        ->expectsOutputToContain('Configuration saved in .scorm file')
        ->assertExitCode(0);

    expect($config)->toBeFile()
        ->and(
            File::get($config)
        )->toContain('version=1.2')
        ->toContain('title=\'Grand Module Perlée\'')
        ->toContain('identifier=van-cleef-arpels-grand-module-perlee')
        ->toContain('source=project-test/dist')
        ->toContain('destination=project-test/scorm')
        ->toContain('masteryScore=80')
        ->toContain('startingPage=index.html')
        ->toContain('organization=\'Van Cleef & Arpels\'')
        ->toContain('packageName=van_cleef_arpels_grand_module_perlee.zip')
        ->and($package)->toBeFile();
});
