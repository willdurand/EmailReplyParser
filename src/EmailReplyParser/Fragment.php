<?php

namespace EmailReplyParser;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class Fragment
{
    /**
     * @var array
     */
    protected $lines = array();

    /**
     * @var boolean
     */
    protected $isHidden = false;

    /**
     * @var boolean
     */
    protected $isSignature = false;

    /**
     * @var boolean
     */
    protected $isQuoted = false;

    /**
     * @param string $firstLine
     * @param boolean $quoted
     */
    public function __construct($firstLine, $quoted = false)
    {
        $this->isQuoted = $quoted;
        $this->lines[]  = $firstLine;
    }

    /**
     * @return boolean
     */
    public function isHidden()
    {
        return $this->isHidden;
    }

    /**
     * @return boolean
     */
    public function isSignature()
    {
        return $this->isSignature;
    }

    /**
     * @var boolean
     */
    public function isQuoted()
    {
        return $this->isQuoted;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return '' === $this->getContent();
    }

    /**
     * @param boolean
     */
    public function setIsSignature($isSignature)
    {
        $this->isSignature = $isSignature;
    }

    /**
     * @param boolean
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;
    }

    /**
     * @param string $line
     */
    public function addLine($line)
    {
        $this->lines[] = $line;
    }

    public function getLastLine()
    {
        return $this->lines[count($this->lines) - 1];
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return preg_replace("/^\n/", '', strrev(implode("\n", $this->lines)));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getContent();
    }
}
