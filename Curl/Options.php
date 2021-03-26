<?php

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Request;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Options
{
    /**
     * @var array Array collection of cURL options with int => "CURLOPT_" pairs.
     */
    private $optionsValueNameMap = [];

    /**
     * @var CookieFile
     */
    private $cookieFile;

    /**
     * @var array Default cURL options.
     */
    private $defaultOptions = [];

    /**
     * @var array The cURL options used to send the last request.
     */
    private $lastOptions = [];

    /**
     * @param CookieFile|null $cookieFile
     * @param array $default
     */
    public function __construct(CookieFile $cookieFile = null, array $default = [])
    {
        $this->setOptionConstants();
        $this->cookieFile = $cookieFile;
        $this->setDefault($default);
    }

    /**
     * Prepares request options.
     *
     * @param array      $options The request cURL options.
     * @param Request    $request
     * @param Stream     $stream
     * @param HeadersBag $headersBag
     *
     * @return array
     */
    public function prepare(array $options, Request $request, &$stream, &$headersBag)
    {
        $options = $this->parse($options) + $this->defaultOptions;
        $options[CURLOPT_URL] = $url = $request->getUrl();
        $options[CURLOPT_HEADER] = false;

        // Set headers
        foreach ($request->getHeaders() as $name => $value) {
            $options[CURLOPT_HTTPHEADER][] = $name . ': ' . $value;
        }

        if ($this->cookieFile) {
            $options[CURLOPT_COOKIEJAR] = $this->cookieFile->getFilename();
            $options[CURLOPT_COOKIEFILE] = $this->cookieFile->getFilename();
        }

        // Set header listener function
        $headersBag = new HeadersBag();
        $options[CURLOPT_HEADERFUNCTION] = function ($handel, $header) use ($headersBag) {
            $headersBag->add(trim($header));

            return strlen($header);
        };
        // Stream
        $options[CURLOPT_RETURNTRANSFER] = false;
        $options[CURLOPT_FILE] = fopen('php://temp', 'w+');
        $stream = new Stream($options[CURLOPT_FILE]);

        $method = strtoupper($request->getMethod());
        $parameters = $request->getParameters();

        if ($method === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            $this->unsetFileAndFunctionOptions($options);
        } elseif ($method !== 'GET' && $method !== 'POST') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        $postTypeMethod = in_array($method, ['POST', 'PUT', 'PATCH']);

        if ($postTypeMethod || $method === 'DELETE') {
            if ($request->getBody()) {
                $options[CURLOPT_POSTFIELDS] = $request->getBody();
            } elseif ($postTypeMethod) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = http_build_query($parameters);
            }
        }

        if (!$postTypeMethod && $parameters) {
            // Add url query from form parameters.
            if (($pos = strpos($url, '?')) !== false) {
                $url = substr($url, 0, $pos);
            }
            $options[CURLOPT_URL] = $url . '?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC3986);
            $request->setUrl($options[CURLOPT_URL]);
        }

        if ($cookies = $request->getCookies()) {
            $options[CURLOPT_COOKIE] = join('; ', $cookies);
        }

        return $this->lastOptions = $options;
    }

    /**
     * Sets default cURL options, which will be applying to every request.
     *
     * @param array $options
     */
    public function setDefault(array $options)
    {
        $defaultOptions = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 200,
            CURLOPT_CONNECTTIMEOUT => 180,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'Client HTTP',
            CURLINFO_HEADER_OUT => true,
        ];

        $this->defaultOptions = $this->parse($options) + $defaultOptions;
    }

    /**
     * @param bool $keysAsConstants
     *
     * @return array
     */
    public function getDefault($keysAsConstants = false)
    {
        return $this->getOptions($this->defaultOptions, $keysAsConstants);
    }

    /**
     * Gets cURL options used to send the last request.
     *
     * @param bool $keysAsConstants
     *
     * @return array
     */
    public function getLast($keysAsConstants = false)
    {
        $options = $this->lastOptions;
        $this->unsetFileAndFunctionOptions($options);
        unset($options[CURLOPT_HEADERFUNCTION]);

        return $this->getOptions($options, $keysAsConstants);
    }

    /**
     * @param array $options
     * @param bool $keysAsConstants
     *
     * @return array
     */
    private function getOptions(array $options, $keysAsConstants)
    {
        if ($keysAsConstants !== true) {
            return $options;
        }

        $_options = [];

        foreach ($options as $key => $value) {
            $_options[$this->optionsValueNameMap[$key]] = $value;
        }

        return $_options;
    }

    /**
     * @param array $options User curl options
     *
     * @return array
     */
    private function parse(array $options)
    {
        $_options = [];

        foreach ($options as $option => $value) {
            $opt = $option;

            if (strpos($option, 'CURLOPT_') !== false || strpos($option, 'CURLINFO_') !== false) {
                $option = @constant($option);
            } elseif (is_string($option)) {
                $option = @constant('CURLOPT_' . strtoupper($option));
            }

            if (!isset($this->optionsValueNameMap[$option])) {
                throw new \InvalidArgumentException('Couldn\'t find cURL constant '. $opt);
            }

            $_options[$option] = $value;
        }

        return $_options;
    }

    /**
     * Gets list of cURL option constants.
     */
    private function setOptionConstants()
    {
        $constants = get_defined_constants(true);

        if (!isset($constants['curl'])) {
            return;
        }

        foreach ($constants['curl'] as $name => $value) {
            if (strpos($name, 'CURLOPT') === 0 || strpos($name, 'CURLINFO') === 0) {
                $this->optionsValueNameMap[$value] = $name;
            }
        }
    }

    private function unsetFileAndFunctionOptions(array &$options)
    {
        unset(
            $options[CURLOPT_WRITEFUNCTION],
            $options[CURLOPT_READFUNCTION],
            $options[CURLOPT_FILE],
            $options[CURLOPT_INFILE]
        );
    }
}
 