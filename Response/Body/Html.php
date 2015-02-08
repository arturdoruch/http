<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Response\Body;

use ArturDoruch\Http\Response\Response;
use ArturDoruch\Http\Response\ResponseBodyInterface;

class Html implements ResponseBodyInterface
{
    private $config = array(
        'orlydb.com' => array('id' => 'releases'),
        'scenebin.com/music/page' => array(
            'className' => 'small-list',
            'item' => 0
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function clean(Response $response)
    {
        $options = null;
        $element = null;
        $url = str_replace(array('http://', 'https://'), '', $response->getEffectiveUrl());

        foreach ($this->config as $host => $opt) {
            if (strpos($url, $host) === 0) {
                $options = $opt;
                break;
            }
        }

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getBody());

        if ($options) {
            if (isset($options['id'])) {
                $element = $dom->getElementById($options['id']);
            } else if (isset($options['className'])) {
                $nodes = \Html::DOMGetElementsByClassName($dom, $options['className']);
                $item = isset($options['item']) ? $options['item'] : 0;
                $element = $nodes->item($item);
            }
        }

        return $element ? utf8_encode(\Html::DOMInnerHTML($element)) : null;
    }
}
 