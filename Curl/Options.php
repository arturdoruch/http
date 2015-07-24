<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\Request;

class Options
{
    /**
     * @var array Default cURL options.
     */
    private $default;

    /**
     * Array collection of cURL options with "CURLOPT_" => int pairs.
     *
     * @var array
     */
    private $curlOptConstants = array();

    /**
     * Array collection of cURL options with int => "CURLOPT_" pairs.
     *
     * @var array
     */
    private $curlOptConstantsHash = array();

    /**
     * @var CookieFile
     */
    private $cookieFile;

    public function __construct(CookieFile $cookieFile)
    {
        $this->cookieFile = $cookieFile;
        $this->setCurlOptConstants();
    }

    /**
     * @param Request $request
     * @param array   $options
     * @return array
     */
    public function parse(Request $request, array $options = array())
    {
        $options = $this->validate($options) + $this->default;
        $options[CURLOPT_URL] = $request->getUrl();
        $options[CURLOPT_COOKIEJAR] = $this->cookieFile->getFilename();
        $options[CURLOPT_COOKIEFILE] = $this->cookieFile->getFilename();

        $method = strtoupper($request->getMethod());
        $params = $request->getParameters();

        if ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        if (($method === 'GET' || $method === 'HEAD') && $params) {
            $options[CURLOPT_URL] .= '?' . http_build_query($params);
        } else {
            if ($request->getBody()) {
                $options[CURLOPT_POSTFIELDS] = $request->getBody();
                $options[CURLOPT_POST] = true;
            } elseif ($params = $request->getParameters()) {
                if (!in_array($method, array('GET', 'HEAD'))) {
                    $options[CURLOPT_POSTFIELDS] = $params = http_build_query($params);
                    $options[CURLOPT_POST] = count($params);
                }
            }
        }

        if ($cookies = $request->getCookies()) {
            if (count($cookies) === 1) {
                $options[CURLOPT_COOKIE] = $cookies[0];
            } else {
                // ToDo Write $cookies in cookies.txt file ?
            }
        }

        if ($headers = $request->getHeaders()) {
            $headerLines = array();
            foreach ($headers as $header => $value) {
                $headerLines[] = $header . ': ' . $value;
            }

            $options[CURLOPT_HTTPHEADER] = $headerLines;
        }

        return $options;
    }

    /**
     * Sets default cURL options, which will be used in every request.
     *
     * @param array $options
     */
    public function setDefault(array $options)
    {
        $defaultOptions = array(
            CURLOPT_USERAGENT => isset($_SERVER['HTTP_USER_AGENT'])
                ? $_SERVER['HTTP_USER_AGENT']
                : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => true,
            CURLOPT_TIMEOUT => 200,
            CURLOPT_CONNECTTIMEOUT => 180,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        );

        $this->default = $this->validate($options) + $defaultOptions;
    }

    /**
     * @return array
     */
    public function getDefault()
    {
        $default = array();
        foreach ($this->default as $number => $value) {
            $default[$this->curlOptConstantsHash[$number]] = $value;
        }

        return $default;
    }


    private function validate(array $options)
    {
        $validOptions = array();
        foreach ($options as $option => $value) {
            $opt = $option;
            if (strpos($option, 'CURLOPT_') !== false) {
                $option = @constant($option);
            } elseif (is_string($option)) {
                $option = @constant('CURLOPT_' . strtoupper($option));
            }

            if (isset($this->curlOptConstantsHash[$option])) {
                $validOptions[$option] = $value;
            } else {
                throw new \InvalidArgumentException('Couldn\'t find cURL constant '. $opt);
            }
        }

        return $validOptions;
    }

    private function setCurlOptConstants()
    {
        $constants = get_defined_constants(true);

        if (isset($constants['curl'])) {
            foreach ($constants['curl'] as $name => $value) {
                if (strpos($name, 'CURLOPT') === 0 || strpos($name, 'CURLINFO') === 0) {
                    $this->curlOptConstants[$name] = $value;
                }
            }

            $this->curlOptConstantsHash = array_flip($this->curlOptConstants);
        }
    }

}
 