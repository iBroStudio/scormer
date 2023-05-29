<?php

namespace App\Actions;

use App\Exceptions\InvalidScormManifestSchemaException;

class BuildManifest
{
    public static function execute(array $elements): string
    {
        $xmlwriter = xmlwriter_open_memory();

        xmlwriter_set_indent($xmlwriter, true);
        xmlwriter_set_indent_string($xmlwriter, '   ');

        xmlwriter_start_document($xmlwriter, '1.0', 'UTF-8');

        foreach ($elements as $element) {
            self::createElement($xmlwriter, $element);
        }

        xmlwriter_end_document($xmlwriter);

        return xmlwriter_output_memory($xmlwriter);
    }

    private static function createElement($xmlwriter, array $element)
    {
        throw_if(
            ! isset($element['name']),
            InvalidScormManifestSchemaException::class, 'InvalidScormManifestSchemaException'
        );

        $elementName = $element['name'];
        $elementAttributes = $element['attributes'] ?? [];
        $elementChilds = $element['childs'] ?? [];
        $elementValue = isset($element['value']) ? ((string) $element['value']) : null;

        xmlwriter_start_element($xmlwriter, $elementName);

        foreach ($elementAttributes as $elementAttributeName => $elementAttributeValue) {
            xmlwriter_write_attribute($xmlwriter, $elementAttributeName, (string) $elementAttributeValue);
        }

        if (! empty($elementChilds)) {
            foreach ($elementChilds as $elementChild) {
                self::createElement($xmlwriter, $elementChild);
            }
        } elseif (! is_null($elementValue)) {
            xmlwriter_text($xmlwriter, $elementValue);
        }

        xmlwriter_end_element($xmlwriter);
    }
}
