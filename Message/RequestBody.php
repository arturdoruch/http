<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Message;

use ArturDoruch\Http\Post\PostFile;

class RequestBody
{
    /**
     * @var string
     */
    private $contentType;

    public function __clone()
    {
        $this->contentType = null;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param array|string $body
     *
     * @return string
     */
    public function parseBody($body)
    {
        if (is_array($body)) {
            if (isset($body['json']) && isset($body['files'])) {
                throw new \InvalidArgumentException('Cannot specified both "json" and "files" keys in request body array.');
            }

            if (isset($body['json'])) {
                return $this->setJson($body['json']);
            } elseif (isset($body['files'])) {
                return $this->setFiles($body['files']);
            }

            return null;
        }
        
        if (is_resource($body)) {
            $body = stream_get_contents($body);
        }

        return $this->setRaw($body);
    }

    /**
     * @param string $body
     *
     * @return string
     */
    private function setRaw($body)
    {
        $this->contentType = 'text/plain';

        return $body;
    }

    /**
     * @param string|array $json Proper json or associative array.
     *
     * @return string
     */
    private function setJson($json)
    {
        if (is_array($json)) {
            $json = json_encode($json);
        } else {
            json_decode($json);
            if ((($error = json_last_error()) !== JSON_ERROR_NONE)) {
                throw new \InvalidArgumentException('Given invalid json string. ' . $error);
            }
        }

        $this->contentType = 'application/json';

        return $json;
    }

    /**
     * @param PostFile[] $postFiles
     *
     * @return string
     */
    private function setFiles(array $postFiles)
    {
        if (empty($postFiles)) {
            return null;
        }

        $content = '';
        $boundary = uniqid();

        foreach ($postFiles as $postFile) {
            if (!$postFile instanceof PostFile) {
                throw new \InvalidArgumentException('Request body array file item must be a
                    ArturDoruch\Http\Post\PostFile instance.');
            }

            $content .= "--" . $boundary . "\r\n";
            $content .= $this->getMultiPartContent($postFile, $boundary);
        }

        $this->contentType = 'multipart/form-data; boundary=' . $boundary;

        return $content . "--" . $boundary . "--\r\n";
    }

    /**
     * @param PostFile $postFile
     *
     * @return string
     */
    private function getMultiPartContent(PostFile $postFile)
    {
        $file = $postFile->getFile();

        if (!file_exists($file)) {
            throw new \RuntimeException(sprintf('The file "%s" which you try to send, is not exist.', $file));
        }

        $filename = basename($postFile->getFilename() ?: $file);
        $fileContent = file_get_contents(realpath($file));

        $body = array(
            sprintf('Content-Disposition: form-data; name="%s"; filename="%s"', $postFile->getName(), $filename),
            'Content-Length: ' . filesize($file),
            'Content-Type: ' . $this->getMimeContentType($file),
            '',
            $fileContent,
            ''
        );

        return implode("\r\n", $body);
    }

    /**
     * @param $filename
     *
     * @return string
     */
    private function getMimeContentType($filename)
    {
        $fileInfo = new \finfo();

        return $fileInfo->file($filename, FILEINFO_MIME_TYPE);
    }

}
 