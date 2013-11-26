<?php

/**
 * This file is part of the EmailReplyParser package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

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
     * @var string
     */
    protected $content = null;

    /**
     * @param string  $firstLine
     * @param boolean $quoted
     */
    public function __construct($quoted = false)
    {
        $this->isQuoted = $quoted;
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
     * @return boolean
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
        return '' === str_replace("\n", '', $this->getContent());
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
     * @param boolean
     */
    public function setIsQuoted($isQuoted)
    {
        $this->isQuoted = $isQuoted;
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
        if (null === $this->content) {
            $this->content = preg_replace("/^\n/", '', strrev(implode("\n", $this->lines)));
        }

        return $this->content;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getContent();
    }
}
