<?php

namespace ErgonTech\Soap\Stream;

/**
 * Class NtlmStream
 * @package ErgonTech\Soap
 * See http://php.net/manual/en/class.streamwrapper.php
 */
class Ntlm
{
    private $path;
    private $mode;
    private $options;
    private $opened_path;
    private $buffer;
    private $pos;
    private $ch;

    public static $user;
    public static $password;

    /**
     * Open the stream
     *
     * @param  $path
     * @param  $mode
     * @param  $options
     * @param  $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, $opened_path)
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;
        $this->opened_path = $opened_path;

        $this->createBuffer($path);

        return true;
    }

    /**
     * Close the stream
     *
     */
    public function stream_close()
    {
        \curl_close($this->ch);
    }

    /**
     * Read the stream
     *
     * @param int $count number of bytes to read
     * @return bool content from pos to count
     */
    public function stream_read($count)
    {
        if (\strlen($this->buffer) == 0) {
            return false;
        }

        $read = \substr($this->buffer,$this->pos, $count);

        $this->pos += $count;

        return $read;
    }

    /**
     * write the stream
     *
     * @param $data number of bytes to read
     * @return bool content from pos to count
     */
    public function stream_write($data)
    {
        if (\strlen($this->buffer) == 0) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return true if eof else false
     */
    public function stream_eof()
    {
        if ($this->pos > \strlen($this->buffer)) {
            return true;
        }
        return false;
    }

    /**
     * @return int the position of the current read pointer
     */
    public function stream_tell()
    {
        return $this->pos;
    }

    /**
     * Flush stream data
     */
    public function stream_flush()
    {
        $this->buffer = null;
        $this->pos = null;
    }

    /**
     * Stat the file, return only the size of the buffer
     * @return array stat information
     */
    public function stream_stat()
    {
        $this->createBuffer($this->path);
        $stat = [
            'size' => \strlen($this->buffer),
        ];

        return $stat;
    }
    /**
     * Stat the url, return only the size of the buffer
     * @return array stat information
     */
    public function url_stat($path, $flags)
    {
        $this->createBuffer($path);
        $stat = [
            'size' => \strlen($this->buffer),
        ];

        return $stat;
    }

    /**
     * Create the buffer by requesting the url through cURL
     * @param $path
     */
    private function createBuffer($path)
    {
        if($this->buffer) {
            return;
        }

        $this->ch = \curl_init($path);
        \curl_setopt($this->ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($this->ch, \CURLOPT_HTTP_VERSION, \CURL_HTTP_VERSION_1_1);
        \curl_setopt($this->ch, \CURLOPT_HTTPAUTH, \CURLAUTH_NTLM);
        \curl_setopt($this->ch, \CURLOPT_USERPWD, self::$user.':'.self::$password);

        $this->buffer = \curl_exec($this->ch);

        $this->pos = 0;
    }
}
