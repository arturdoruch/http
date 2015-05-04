<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Cookie;

class CookieFile
{
    /**
     * @var string
     */
    private $filename;

    public function __construct($filename = null)
    {
        $filename = $filename ?: __DIR__ . '/cookies.txt';
        $this->setFile($filename);
    }

    /**
     * @param string $filename
     *
     * @throws \Exception
     */
    public function setFile($filename)
    {
        if (!file_exists($filename)) {
            file_put_contents($filename, '');
        }

        if (!is_writable($filename)) {
            throw new \RuntimeException(sprintf(
                    'The cookie file "%s" is not writable. Permissions denied.', $filename
                ));
        }

        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

}
 