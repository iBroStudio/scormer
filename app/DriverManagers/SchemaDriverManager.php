<?php

namespace App\DriverManagers;

use App\SchemaManagers\Scorm12SchemaManager;
use App\SchemaManagers\Scorm2004Edition3SchemaManager;
use App\SchemaManagers\Scorm2004Edition4SchemaManager;
use Illuminate\Support\Manager;

class SchemaDriverManager extends Manager
{
    public function getDefaultDriver()
    {
        return 'scorm12';
    }

    public function createScorm12Driver()
    {
        return new Scorm12SchemaManager;
    }

    public function createScorm2004Ed3Driver()
    {
        return new Scorm2004Edition3SchemaManager;
    }

    public function createScorm2004Ed4Driver()
    {
        return new Scorm2004Edition4SchemaManager;
    }
}
