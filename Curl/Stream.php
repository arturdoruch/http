<?php

namespace ArturDoruch\Http\Curl;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Stream
{
    /**
     * @var resource
     */
    private $stream;
    private $seekable;
    private $readable;
    private $writable;

    /**
     * @param resource $stream Stream resource to wrap.
     *
     * @throws \InvalidArgumentException if the stream is not a stream resource.
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource.');
        }

        $this->stream = $stream;
        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'];
        $this->readable = isset(self::$readWriteHash['read'][$meta['mode']]);
        $this->writable = isset(self::$readWriteHash['write'][$meta['mode']]);
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return string
     */
    public function getContents()
    {
        $this->rewind();

        if (false === $contents = stream_get_contents($this->stream)) {
            throw new \RuntimeException('Unable to read stream contents.');
        }

        return $contents;
    }

    /**
     * Closes and unset the stream.
     */
    public function close()
    {
        if (!isset($this->stream)) {
            return;
        }

        fclose($this->stream);

        unset($this->stream);
        $this->readable = $this->writable = $this->seekable = false;
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    private function rewind()
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        if (fseek($this->stream, 0) === -1) {
            throw new \RuntimeException('Unable rewind stream to the begin.');
        }
    }

    /**
     * @var array Hash of readable and writable stream types
     */
    private static $readWriteHash = [
        'read' => [
            'r' => true,
            'w+' => true,
            'r+' => true,
            'x+' => true,
            'c+' => true,
            'rb' => true,
            'w+b' => true,
            'r+b' => true,
            'x+b' => true,
            'c+b' => true,
            'rt' => true,
            'w+t' => true,
            'r+t' => true,
            'x+t' => true,
            'c+t' => true,
            'a+' => true
        ],
        'write' => [
            'w' => true,
            'w+' => true,
            'rw' => true,
            'r+' => true,
            'x+' => true,
            'c+' => true,
            'wb' => true,
            'w+b' => true,
            'r+b' => true,
            'x+b' => true,
            'c+b' => true,
            'w+t' => true,
            'r+t' => true,
            'x+t' => true,
            'c+t' => true,
            'a' => true,
            'a+' => true
        ]
    ];
}
