<?php

namespace ArturDoruch\Http\Message;

/**
 * @internal
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class RequestBodyCompiler
{
    /**
     * Compiles request body with different content types into string.
     *
     * @param array|string $body
     * @param string $contentType The content type determined based on the body.
     *
     * @return string
     */
    public static function compile($body, &$contentType)
    {
        $contentType = 'text/plain';

        if (is_array($body)) {
            if (isset($body['json']) && isset($body['multipart'])) {
                throw new \InvalidArgumentException('An array with the request body must not specify both the "json" and the "multipart" key.');
            }

            if (isset($body['json'])) {
                $contentType = 'application/json';

                return self::compileJson($body['json']);
            }

            if (isset($body['multipart'])) {
                if (!is_array($body['multipart'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid request body. An array with "multipart" key must be type of array, but got "%s".', gettype($body['multipart'])
                    ));
                }

                return self::compileMultipartData($body['multipart'], $contentType);
            }
        } elseif (is_resource($body)) {
            return stream_get_contents($body);
        } elseif (is_scalar($body)) {
            return (string) $body;
        }

        throw new \InvalidArgumentException('Invalid request body. Expected plain text, resource, or an array with the "json" or "multipart" key.');
    }

    /**
     * @param string|array $json
     *
     * @return string
     */
    private static function compileJson($json)
    {
        if (is_array($json)) {
            $json = json_encode($json);
        } else {
            json_decode($json);
            if ((($error = json_last_error()) !== JSON_ERROR_NONE)) {
                throw new \InvalidArgumentException('Invalid JSON of the request body. ' . $error);
            }
        }

        return $json;
    }

    /**
     * Compiles multipart form data.
     *
     * @param array $formData
     * @param string $contentType
     *
     * @return string
     */
    private static function compileMultipartData(array $formData, &$contentType)
    {
        $contentType = 'multipart/form-data; boundary=' . ($boundary = sha1(uniqid()));
        $content = '';

        foreach ($formData as $name => $value) {
            $content .= self::createMultipartContent($boundary, $name, $value);
        }

        return $content . "--" . $boundary . "--\r\n";
    }


    private static function createMultipartContent($boundary, $name, $value)
    {
        $content = '';

        if (is_object($value)) {
            if (!$value instanceof FormFile) {
                throw new \InvalidArgumentException(sprintf(
                    'An array key "multipart" with the request body must contains form data with the values type' .
                    ' of scalar or the "%s" instances.'
                ), FormFile::class);
            }

            if (!file_exists($file = $value->getFile())) {
                throw new \RuntimeException(sprintf('The file "%s" which you try to send, is not exist.', $file));
            }

            $filename = basename($value->getFilename() ?: $file);
            $content .= implode("\r\n", [
                '--' . $boundary,
                sprintf('Content-Disposition: form-data; name="%s"; filename="%s"', $name, $filename),
                'Content-Length: ' . filesize($file),
                'Content-Type: ' . (new \finfo())->file($file, FILEINFO_MIME_TYPE),
                '',
                file_get_contents(realpath($file)),
                ''
            ]);
        } elseif (is_array($value)) {
            foreach ($value as $n => $v) {
                $content .= self::createMultipartContent($boundary, $name . sprintf('[%s]', is_int($n) ? '' : $n), $v);
            }
        } else {
            $content .= implode("\r\n", [
                '--' . $boundary,
                sprintf('Content-Disposition: form-data; name="%s"', $name),
                //'Content-Length: ' . strlen($value),
                'Content-Type: text/plain',
                '',
                $value,
                ''
            ]);
        }

        return $content;
    }
}
 