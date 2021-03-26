<?php

namespace ArturDoruch\Http\Post;

use ArturDoruch\Http\Message\FormFile;

/**
 * @deprecated Use the "ArturDoruch\Http\Message\FormFile" class instead.
 *
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class PostFile extends FormFile
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name Form name.
     * @param string $file The phat to file, to send.
     * @param string|null $filename Custom file name.
     */
    public function __construct($name, $file, $filename = null)
    {
        $this->name = $name;
        parent::__construct($file, $filename);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
 