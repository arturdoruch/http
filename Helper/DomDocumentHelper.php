<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Helper;


class DOMDocumentHelper
{
    public static function getInnerHTML(\DOMNode $element)
    {
        $innerHTML = '';
        $children = $element->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }

    public static function getElementsByClassName(\DOMDocument $dom, $className)
    {
        $xpath = new \DomXPath($dom);

        return $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' ".$className." ')]");
    }

    public static function removeNoise($html)
    {
        $noise = array(
            '<!--(.*?)-->',
            '<!\[CDATA\[(.*?)\]\]>',
            '<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>',
            '<\s*script\s*>(.*?)<\s*/\s*script\s*>',
            '<\s*noscript[^>]*>(.*?)<\s*/\s*noscript\s*>',
            '<\s*iframe[^>]*[^/]>(.*?)<\s*/\s*iframe\s*>',
            '<\s*(img|meta|input)(.*?)([/]?\s*)>',
            //<input type="image" src="/templates/maniacs_dle/images/send.png" name="image">
        );

        foreach ($noise as $pattern) {
            $html = preg_replace("@{$pattern}@is", '', $html);
        }

        return $html;
    }


    public static function removeWhiteSpace($html)
    {
        return preg_replace('/(?<=>)\s+(?=<)|(?<=>)\s+(?!=<)|(?!<=>)\s+(?=<)/i', '', $html);
    }

}
 