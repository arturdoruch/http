<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Util;


class HtmlUtils
{
    /**
     * @param string $html
     * @param bool   $image         If true removes all img tags and input tags with type image from html document.
     * @param bool   $inputAndMeta  If true removes all input and meta tags from html document.
     * @param bool   $script        If true removes all script, nonscript na iframe tags from html document.
     *
     * @return string
     */
    public static function removeNoise(&$html, $image = true, $inputAndMeta = true, $script = true)
    {
        $noise = array(
            '<!(?=.*--.*)[^>]{4,}>',
            '<!\[CDATA\[[^\]]+\]\]>',
        );

        if ($script === true) {
            $noise[] = '<\s*(script|noscript|iframe)[^>]*>[^>]*<\s*\/\s*\1\s*>';
        }

        if ($inputAndMeta === true) {
            $noise[] = '<\s*(meta|input)(?!.*type="image")[^>]+>';
        }

        if ($image === true) {
            $noise[] = '<\s*img[^>]+>';
            $noise[] = '<\s*input[^>]+type="image"[^>]+>';
        }

        foreach ($noise as $pattern) {
            $html = preg_replace("@{$pattern}@is", '', $html);
        }
    }


    /**
     * Removes white spaces from string code.
     *
     * @param string $html
     *
     * @return string
     */
    public static function removeWhiteSpace(&$html)
    {
        $html = preg_replace('/(?<=>)\s+(?=<)|(?<=>)\s+(?!=<)|(?!<=>)\s+(?=<)/i', '', $html);
    }

    /**
     * @param string $html
     *
     * @return mixed
     */
    public static function removeBlankLines(&$html)
    {
        $html = preg_replace('/(?:[\t ]*(?:\r?\n|\r))+/i', "\n", $html);
    }

}
