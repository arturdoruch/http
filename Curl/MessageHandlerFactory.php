<?php

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\Request;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class MessageHandlerFactory
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @param Options $options
     */
    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * @param Request $request
     * @param array   $curlOptions
     *
     * @return MessageHandler
     */
    public function create(Request $request, array $curlOptions)
    {
        $options = $this->options->prepare($curlOptions, $request, $stream, $headersBag);

        return new MessageHandler($request, $options, $stream, $headersBag);
    }
}
