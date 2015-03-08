<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\RequestParameter;

class Options
{
    /**
     * @var array Default cURL options
     */
    private $default;

    /**
     * @var string
     */
    private $cookieFile;

    public function __construct($cookieFile)
    {
        $this->cookieFile = $cookieFile ?: __DIR__ . '/cookies.txt';
    }

    /**
     * @param string $cookieFile
     */
    public function setCookieFile($cookieFile)
    {
        $this->cookieFile = $cookieFile;
    }

    /**
     * @param string           $url
     * @param RequestParameter $parameters
     * @param array            $options
     * @return array
     */
    public function parse($url = null, RequestParameter $parameters = null, array $options = array())
    {
        $options = $this->set($options);
        $options[CURLOPT_URL] = $url;

        if ($parameters) {
            if ($url = $parameters->getUrl()) {
                $options[CURLOPT_URL] = $url;
            }

            if ($params = $parameters->getParameters()) {
                $method = $parameters->getMethod();
                if ($method == 'POST') {
                    $params = http_build_query($params);

                    $options[CURLOPT_POSTFIELDS] = $params;
                    $options[CURLOPT_POST] = count($params);
                } elseif ($method == 'GET') {
                    $options[CURLOPT_URL] .= '?' . http_build_query($params);
                } else {
                    //$options[CURLOPT_CUSTOMREQUEST] = "POST";
                    //$options[CURLOPT_VERBOSE] = true;
                }
            }

            if ($cookies = $parameters->getCookies()) {
                if (count($cookies) === 1) {
                    $options[CURLOPT_COOKIE] = $cookies[0];
                } else {
                    // ToDo Write $cookies in cookies.txt file ?
                }
            }

            if ($headers = $parameters->getHeaders()) {
                $options[CURLOPT_HTTPHEADER] = $headers;
            }
        }

        return $options;
    }

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
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            155 => 90000,
        );

        $this->default = $this->validate($options) + $defaultOptions;
    }

    /**
     * Sets cURL options.
     *
     * @param array $options
     * @return array
     */
    public function set(array $options)
    {
        return $this->default = $this->validate($options) + $this->default;
    }


    private function validate(array $options)
    {
        $validOptions = array();
        foreach ($options as $option => $value) {
            if (!is_numeric($option)) {
                if ($opt = @constant('CURLOPT_' . strtoupper($option))) {
                    $validOptions[$opt] = $value;
                }
            }
        }

        return $validOptions;
    }
}
 