<?php

namespace App\SchemaManagers;

use App\Contracts\ScormSchemaManager;
use App\Data\ScormSchemaData;

class Scorm2004Edition3SchemaManager extends AbstractScormSchemaManager implements ScormSchemaManager
{
    public static function getSchema(ScormSchemaData $data): array
    {
        $schemaIdentifier = self::getSchemaIdentifier($data->identifier);
        $itemIdentifier = self::getItemIdentifier($data->identifier);
        $identifierRef = self::getIdentifierRef($data->identifier);
        $schemaOrganization = self::getSchemaOrganization($data->organization);

        return [
            [
                'name' => 'manifest',
                'attributes' => [
                    'identifier' => $schemaIdentifier,
                    'version' => 1,
                    'xmlns:adlnav' => 'http://www.adlnet.org/xsd/adlnav_v1p3',
                    'xmlns' => 'http://www.imsglobal.org/xsd/imscp_v1p1',
                    'xmlns:adlseq' => 'http://www.adlnet.org/xsd/adlseq_v1p3',
                    'xmlns:imsss' => 'http://www.imsglobal.org/xsd/imsss',
                    'xmlns:adlcp' => 'http://www.adlnet.org/xsd/adlcp_v1p3',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.imsglobal.org/xsd/imscp_v1p1 definitionFiles/imscp_v1p1.xsd '.
                        'http://www.adlnet.org/xsd/adlcp_v1p3 definitionFiles/adlcp_v1p3.xsd '.
                        'http://www.adlnet.org/xsd/adlseq_v1p3 definitionFiles/adlseq_v1p3.xsd '.
                        'http://www.adlnet.org/xsd/adlnav_v1p3 definitionFiles/adlnav_v1p3.xsd '.
                        'http://www.imsglobal.org/xsd/imsss definitionFiles/imsss_v1p0.xsd',
                ],
                'childs' => [
                    [
                        'name' => 'metadata',
                        'childs' => [
                            [
                                'name' => 'schema',
                                'value' => 'ADL SCORM',
                            ],
                            [
                                'name' => 'schemaversion',
                                'value' => '2004 3rd Edition',
                            ],
                        ],
                    ],
                    [
                        'name' => 'organizations',
                        'attributes' => [
                            'default' => $schemaOrganization,
                        ],
                        'childs' => [
                            [
                                'name' => 'organization',
                                'attributes' => [
                                    'identifier' => $schemaOrganization,
                                ],
                                'childs' => [
                                    [
                                        'name' => 'title',
                                        'value' => $data->title,
                                    ],
                                    [
                                        'name' => 'item',
                                        'attributes' => [
                                            'identifier' => $itemIdentifier,
                                            'identifierref' => $identifierRef,
                                        ],
                                        'childs' => [
                                            [
                                                'name' => 'title',
                                                'value' => $data->title,
                                            ],
                                            [
                                                'name' => 'imsss:sequencing',
                                                'childs' => [
                                                    [
                                                        'name' => 'imsss:objectives',
                                                        'childs' => [
                                                            [
                                                                'name' => 'imsss:primaryObjective',
                                                                'attributes' => [
                                                                    'objectiveID' => 'PRIMARYOBJ',
                                                                    'satisfiedByMeasure' => 'true',
                                                                ],
                                                                'childs' => [
                                                                    [
                                                                        'name' => 'imsss:minNormalizedMeasure',
                                                                        'value' => $data->masteryScore / 100,
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                    [
                                                        'name' => 'imsss:deliveryControls',
                                                        'attributes' => [
                                                            'completionSetByContent' => 'true',
                                                            'objectiveSetByContent' => 'true',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'imsss:sequencing',
                                        'childs' => [
                                            [
                                                'name' => 'imsss:controlMode',
                                                'attributes' => [
                                                    'choice' => 'true',
                                                    'flow' => 'true',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'resources',
                        'childs' => [
                            [
                                'name' => 'resource',
                                'attributes' => [
                                    'identifier' => $identifierRef,
                                    'type' => 'webcontent',
                                    'href' => $data->startingPage,
                                    'adlcp:scormType' => 'sco',
                                ],
                                'childs' => self::getFiles($data->pathToDirectory),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
