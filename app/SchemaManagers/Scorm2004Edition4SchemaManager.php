<?php

namespace App\SchemaManagers;

use App\Contracts\MetadataSchemaManager;
use App\Contracts\ScormSchemaManager;
use App\Data\ScormSchemaData;
use App\SchemaManagers\Metadata\Scorm2004Edition4MetadataSchemaManager;

class Scorm2004Edition4SchemaManager extends AbstractScormSchemaManager implements ScormSchemaManager, MetadataSchemaManager
{
    use Scorm2004Edition4MetadataSchemaManager;

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
                    'version' => 1.3,
                    'xmlns:adlnav' => 'http://www.adlnet.org/xsd/adlnav_v1p3',
                    'xmlns:lom' => 'http://ltsc.ieee.org/xsd/LOM',
                    'xmlns' => 'http://www.imsglobal.org/xsd/imscp_v1p1',
                    'xmlns:adlseq' => 'http://www.adlnet.org/xsd/adlseq_v1p3',
                    'xmlns:imsss' => 'http://www.imsglobal.org/xsd/imsss',
                    'xmlns:adlcp' => 'http://www.adlnet.org/xsd/adlcp_v1p3',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd '.
                        'http://www.adlnet.org/xsd/adlcp_v1p3 adlcp_v1p3.xsd '.
                        'http://www.adlnet.org/xsd/adlseq_v1p3 adlseq_v1p3.xsd '.
                        'http://www.adlnet.org/xsd/adlnav_v1p3 adlnav_v1p3.xsd '.
                        'http://www.imsglobal.org/xsd/imsss imsss_v1p0.xsd '.
                        'http://ltsc.ieee.org/xsd/LOM lom.xsd',
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
                                'value' => '2004 4th Edition',
                            ],
                            [
                                'name' => 'adlcp:location',
                                'value' => 'metadata.xml',
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
                                    'adlseq:objectivesGlobalToSystem' => false,
                                    'structure' => 'hierarchical',
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
                                            'isvisible' => true,
                                        ],
                                        'childs' => [
                                            [
                                                'name' => 'title',
                                                'value' => $data->title,
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'metadata',
                                        'childs' => [
                                            [
                                                'name' => 'lom',
                                                'attributes' => [
                                                    'xmlns' => 'http://ltsc.ieee.org/xsd/LOM',
                                                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                                                    'xsi:schemaLocation' => 'http://ltsc.ieee.org/xsd/LOM lom.xsd',
                                                ],
                                            ],
                                            [
                                                'name' => 'adlcp:location',
                                                'value' => 'metadata.xml',
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'imsss:sequencing',
                                        'childs' => [
                                            [
                                                'name' => 'imsss:controlMode',
                                                'attributes' => [
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
