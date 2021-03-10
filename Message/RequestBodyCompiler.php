<?php

namespace ArturDoruch\Http\Message;

use ArturDoruch\Http\Post\PostFile;

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
            if (isset($body['json']) && isset($body['files'])) {
                throw new \InvalidArgumentException('An array with the request body must not specify both the "json" and the "files" key.');
            }

            if (isset($body['json'])) {
                $contentType = 'application/json';

                return self::compileJson($body['json']);
            }

            if (isset($body['files'])) {
                return self::compileFiles($body['files'], $contentType);
            }
        } elseif (is_resource($body)) {
            return stream_get_contents($body);
        } elseif (is_scalar($body)) {
            return (string) $body;
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid request body. Expected plain text, resource, or an array with the "json" or "files" key, but got %s.',
            is_array($body) ? sprintf('array with keys: "%s"', join('", "', array_keys($body))) : gettype($body)
        ));
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
     * @param PostFile[] $postFiles
     * @param string $contentType
     *
     * @return string
     */
    private static function compileFiles(array $postFiles, &$contentType)
    {
        $contentType = 'multipart/form-data; boundary=' . ($boundary = uniqid());
        $content = '';

        foreach ($postFiles as $postFile) {
            if (!$postFile instanceof PostFile) {
                throw new \InvalidArgumentException(
                    'An array key "files" with the request body must contains list of the "ArturDoruch\Http\Post\PostFile" instances.'
                );
            }

            $content .= "--" . $boundary . "\r\n";
            $content .= self::createMultiPartContent($postFile);
        }

        return $content . "--" . $boundary . "--\r\n";
    }

    /**
     * @param PostFile $postFile
     *
     * @return string
     */
    private static function createMultiPartContent(PostFile $postFile)
    {
        if (!file_exists($file = $postFile->getFile())) {
            throw new \RuntimeException(sprintf('The file "%s" which you try to send, is not exist.', $file));
        }

        $filename = basename($postFile->getFilename() ?: $file);

        return implode("\r\n", [
            sprintf('Content-Disposition: form-data; name="%s"; filename="%s"', $postFile->getName(), $filename),
            'Content-Length: ' . filesize($file),
            'Content-Type: ' . (new \finfo())->file($file, FILEINFO_MIME_TYPE),
            '',
            file_get_contents(realpath($file)),
            ''
        ]);
    }
}
 