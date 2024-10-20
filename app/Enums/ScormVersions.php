<?php

namespace App\Enums;

use App\Data\ScormConfigData;
use App\Data\ScormConfigWithMetadataData;

enum ScormVersions: string
{
    case SCORM_1_2 = '1.2';
    case SCORM_2004_3 = '2004.3';
    case SCORM_2004_4 = '2004.4';

    public static function options(): array
    {
        return [
            self::SCORM_1_2->value => 'Scorm 1.2',
            self::SCORM_2004_3->value => 'Scorm 2004 3rd Edition',
            self::SCORM_2004_4->value => 'Scorm 2004 4th Edition',
        ];
    }

    public function getConfigDataClass(): string
    {
        return match ($this) {
            self::SCORM_1_2, self::SCORM_2004_3 => ScormConfigData::class,
            self::SCORM_2004_4 => ScormConfigWithMetadataData::class,
        };
    }

    public function getDriver(): string
    {
        return match ($this) {
            self::SCORM_1_2 => 'scorm12',
            self::SCORM_2004_3 => 'scorm2004Ed3',
            self::SCORM_2004_4 => 'scorm2004Ed4',
        };
    }
}
