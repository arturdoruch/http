<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Response\Body;

use ArturDoruch\Http\Response\Response;
use ArturDoruch\Http\Response\ResponseBodyInterface;
use ArturDoruch\Http\Helper\DOMDocumentHelper;

class Html implements ResponseBodyInterface
{
    private $config = array(
        'php.net' => array('id' => 'layout-content'),
        'twitter.com' => array(
            'className' => 'front-card',
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
                $nodes = DOMDocumentHelper::getElementsByClassName($dom, $options['className']);
                $item = isset($options['item']) ? $options['item'] : 0;
                $element = $nodes->item($item);
            }
        }

        return $element ? utf8_encode(DOMDocumentHelper::getInnerHTML($element)) : null;
    }
}
 