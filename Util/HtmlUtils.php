<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Util;

class HtmlUtils
{
    /**
     * Removes unwanted elements and tags from html code.
     *
     * @param string $html The html code.
     * @param array|null $elements
     * The array with names of html elements to remove.
     * If array is empty, non of elements will be removed.
     * If null, the default elements will be removed: comment, script, style.
     *
     * Available elements names and their meaning:
     *  - head        head tag with html tag. Only body tag will be left
     *  - comment     comments except IE hacks
     *  - script      script, noscript and iframe tags
     *  - style       style elements
     *  - img         img tags and input tags with type of image
     *  - input_meta  input and meta tags, except input with type of image
     *  - all         all elements mentioned above
     * @param bool $removeEmptyLines If true removes empty lines.
     *
     * @return string
     */
    public static function removeNoise(&$html, array $elements = null, $removeEmptyLines = true)
    {
        $elements = array_flip($elements ?: array('comment', 'script', 'style'));

        $patterns = array();
        $removeAll = isset($elements['all']);
        $removeScript = isset($elements['script']) || $removeAll;

        if (isset($elements['head']) || $removeAll) {
            if (preg_match('/(<\s*body[^>]*>.*<\/body>)/is', $html, $matches)) {
                $html = $matches[1];
            } else {
                $patterns[] = '<\s*head[^>]*>.+<\s*/\s*head\s*>';
            }
        }

        if ($removeScript) {
            $patterns[] = '<\s*(script|noscript|iframe)[^>]*>.*?<\s*/\s*\1\s*>';
        }

        if (isset($elements['comment']) || $removeAll) {
            self::removeComments($html, $removeScript);
        }

        if (isset($elements['style']) || $removeAll) {
            $patterns[] = '<\s*style[^>]*>.*?<\s*/\s*style\s*>';
        }

        if (isset($elements['input_meta']) || $removeAll) {
            $patterns[] = '<\s*(meta|input)(?![^>]*type="image")[^>]+>';
        }

        if (isset($elements['img']) || $removeAll) {
            $patterns[] = '<\s*img[^>]*>';
            $patterns[] = '<\s*input[^>]+type="image"[^>]+>';
        }

        foreach ($patterns as $pattern) {
            $html = preg_replace('@' . $pattern . '@is', '', $html);
        }

        if ($removeEmptyLines === true) {
            self::removeEmptyLines($html);
        }
    }

    /**
     * Minify the html code.
     *
     * @param string $html
     * @param bool   $removeComments Remove html comments except Internet Explorer hacks.
     *
     * @return string
     */
    public static function minify(&$html, $removeComments = true)
    {
        $html = preg_replace(array(
                '/>\s+/',
                '/\s+</',
                '/[\n\t\r]/',
                '/\s{2,}/'
            ), array(
                '>',
                '<',
                '',
                ' '
            ), $html);

        if ($removeComments === true) {
            self::removeComments($html);
        }
    }

    /**
     * Remove comments from html code.
     *
     * @param string $html The html code
     * @param bool   $removeIEHacks
     */
    public static function removeComments(&$html, $removeIEHacks = false)
    {
        $regexpPart = $removeIEHacks === false ? '(?!\[if.*endif\])' : '';

        $html = preg_replace('/<!--'. $regexpPart .'.*?-->/is', '', $html);
    }

    /**
     * Removes empty lines from text.
     *
     * @param string $text
     *
     * @return string
     */
    public static function removeEmptyLines(&$text)
    {
        $text = trim(preg_replace("/([\t ]*(\r?\n|\r))+/i", "\n", $text));
    }

}
