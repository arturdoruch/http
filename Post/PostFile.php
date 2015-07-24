<?php
/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */

namespace ArturDoruch\Http\Post;


class PostFile
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $file;

    /**
     * @var null|string
     */
    private $filename;

    /**
     * @param string $name
     * @param string $file
     * @param string|null $filename
     */
    public function __construct($name, $file, $filename = null)
    {
        $this->name = $name;
        $this->file = $file;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
 