<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\RequestParameter;

class Options
{
    /**
     * @var array Default cURL options.
     */
    private $default;

    /*
     * @var array cURL options to use only once with first request.
     */
    //private $requestOptions;

    /**
     * @var string
     */
    private $cookieFile;

    public function __construct($cookieFile = null)
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
        $options = $this->validate($options) + $this->default;
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
            CURLOPT_USERAGENT =>
                isset($_SERVER['HTTP_USER_AGENT'])
                ? $_SERVER['HTTP_USER_AGENT']
                : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0',
                //'Guzzle/5.2.0 curl/7.35.0 PHP/5.5.22-1+deb.sury.org~trusty+1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 15000,
        );

        $this->default = $this->validate($options) + $defaultOptions;
    }

    public function getDefault()
    {
        return $this->default;
    }

    /*
     * @param array $options
     */
    /*public function setRequestOptions(array $options)
    {
        $this->requestOptions = $this->validate($options);
    }*/

    private function validate(array $options)
    {
        $validOptions = array();
        foreach ($options as $option => $value) {
            if (strpos($option, 'CURLOPT_') !== false) {
                $validOptions[$option] = $value;
            } elseif (is_string($option)) {
                if ($opt = @constant('CURLOPT_' . strtoupper($option))) {
                    $validOptions[$opt] = $value;
                }
            } else {
                $validOptions[(int) $option] = $value;
            }
        }

        return $validOptions;
    }
}
 