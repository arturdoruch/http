<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Util;


class HtmlUtils
{
    /**
     * Removes unwanted tags from html code.
     *
     * @param string $html
     * @param bool   $image         Removes tags: img, input[type="image"].
     * @param bool   $inputAndMeta  Removes tags: input, meta.
     * @param bool   $script        Removes tags: script, nonscript, iframe.
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
