<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Cookie;

/**
 * Class for managing session cookie file.
 */
class CookieFile
{
    /**
     * Cookie file name
     *
     * @var string
     */
    private $filename;

    /**
     * @param null|string $filename Cookie file name.
     */
    public function __construct($filename = null)
    {
        $filename = $filename ?: __DIR__ . '/cookies.txt';
        $this->setFile($filename);
    }

    /**
     * @param string $filename Cookie file name.
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
 