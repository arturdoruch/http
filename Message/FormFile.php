<?php

namespace ArturDoruch\Http\Message;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class FormFile
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $filename;

    /**
     * @param string $file Path to the file.
     * @param string|null $filename Own file name to be sent.
     */
    public function __construct($file, $filename = null)
    {
        $this->file = $file;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return null|string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
 