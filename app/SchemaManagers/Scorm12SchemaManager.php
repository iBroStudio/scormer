<?php

namespace App\SchemaManagers;

use App\Contracts\ScormSchemaManager;
use App\Data\ScormSchemaData;

class Scorm12SchemaManager extends AbstractScormSchemaManager implements ScormSchemaManager
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
                    'xmlns:adlcp' => 'http://www.adlnet.org/xsd/adlcp_rootv1p2',
                    'xmlns' => 'http://www.imsproject.org/xsd/imscp_rootv1p1p2',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.imsproject.org/xsd/imscp_rootv1p1p2 definitionFiles/imscp_rootv1p1p2.xsd '.
                        'http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 definitionFiles/imsmd_rootv1p2p1.xsd '.
                        'http://www.adlnet.org/xsd/adlcp_rootv1p2 definitionFiles/adlcp_rootv1p2.xsd',
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
                                'value' => '1.2',
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
                                                'name' => 'adlcp:masteryscore',
                                                'value' => $data->masteryScore,
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
