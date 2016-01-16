<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Curl;

use ArturDoruch\Http\Cookie\CookieFile;
use ArturDoruch\Http\RequestHandler;

class Options
{
    /**
     * Default cURL options.
     *
     * @var array
     */
    private $defaultOptions = array();

    /**
     * The last request cURL options.
     *
     * @var array
     */
    private $options = array();

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

    public function __construct(CookieFile $cookieFile = null)
    {
        $this->cookieFile = $cookieFile;
        $this->setCurlOptConstants();
    }

    /**
     * @param RequestHandler $handler
     * @param array $options User cURL options
     */
    public function prepareOptions(RequestHandler $handler, array $options = array())
    {
        $request = $handler->getRequest();

        $options = $this->validate($options) + $this->defaultOptions;
        $options[CURLOPT_URL] = $url = $request->getUrl();
        $options[CURLOPT_HEADER] = false;
        //$options[CURLOPT_HTTPHEADER][] = 'Host: ' . parse_url($request->getUrl(), PHP_URL_HOST);

        foreach ($request->getHeaders() as $header => $value) {
            $options[CURLOPT_HTTPHEADER][] = $header . ': ' . $value;
        }

        $options[CURLOPT_HEADERFUNCTION] = function ($ch, $header) use ($handler) {
            $handler->addHeader((int) $ch, trim($header));

            return strlen($header);
        };

        if ($this->cookieFile) {
            $options[CURLOPT_COOKIEJAR] = $this->cookieFile->getFilename();
            $options[CURLOPT_COOKIEFILE] = $this->cookieFile->getFilename();
        }

        $method = strtoupper($request->getMethod());
        $params = $request->getParameters();

        if ($method === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
        } elseif ($method !== 'GET' && $method !== 'POST') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        $isFormMethod = in_array($method, array('POST', 'PUT', 'PATCH'));

        if ($method !== 'GET' && $method !== 'HEAD') {
            if ($request->getBody()) {
                $options[CURLOPT_POSTFIELDS] = $request->getBody();
            } elseif ($isFormMethod) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = http_build_query($params);
            }
        }

        if (!$isFormMethod && $params) {
            // Add url query from form parameters.
            if (($pos = strpos($url, '?')) !== false) {
                $url = substr($url, 0, $pos);
            }
            $options[CURLOPT_URL] = $url . '?' . http_build_query($params);
            $request->setUrl($options[CURLOPT_URL]);
        }

        if ($cookies = $request->getCookies()) {
            if (count($cookies) > 0) {
                $options[CURLOPT_COOKIE] = $cookies[0];
            }
        }

        $this->options = $options;
        $handler->setOptions($options);
    }

    /**
     * Sets default cURL options, which will be used in every request.
     *
     * @param array $options
     */
    public function setDefaultOptions(array $options)
    {
        $defaultOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 200,
            CURLOPT_CONNECTTIMEOUT => 180,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => array(
                'Accept-Encoding: ',
                'User-Agent: Client http'
            ),
            // CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            // CURLOPT_FILE => fopen('php://temp', 'w+'),
        );

        $this->defaultOptions = $this->validate($options) + $defaultOptions;
    }

    /**
     * @param bool $keyAsConstantName
     * @return array
     */
    public function getDefaultOptions($keyAsConstantName = false)
    {
        return $this->collectOptions($this->defaultOptions, $keyAsConstantName);
    }

    /**
     * Gets current cURL options used with the last request.
     *
     * @param bool $keyAsConstantName
     *
     * @return array
     */
    public function getOptions($keyAsConstantName = false)
    {
        return $this->collectOptions($this->options, $keyAsConstantName);
    }

    /**
     * @param array $options
     * @param bool $keyAsConstantName
     * @return array
     */
    private function collectOptions(array $options, $keyAsConstantName = false)
    {
        if ($keyAsConstantName === false) {
            return $options;
        }

        $curlOptions = array();
        foreach ($options as $number => $value) {
            $curlOptions[$this->curlOptConstantsHash[$number]] = $value;
        }

        return $curlOptions;
    }


    private function validate(array $options)
    {
        $validOptions = array();
        foreach ($options as $option => $value) {
            $opt = $option;
            if (strpos($option, 'CURLOPT_') !== false || strpos($option, 'CURLINFO_') !== false) {
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
 