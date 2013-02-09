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
class Email
{
    const SIG_REGEX = '/(--|__|\w-$)|(^(\w+\s*){1,3} ym morf tneS$)/s';

    protected $quote_headers_regex = array (
        '/^(On\s(.+)wrote:)$/ms', // On DATE, NAME <EMAIL> wrote:
    );
    protected $quote_headers_regex_reverse = array (
        '/^:etorw.*nO$/s',
    );

    /**
     * @var array
     */
    protected $fragments = array();

    /**
     * Read a text which represents an email and splits it into fragments.
     *
     * @param  string $text A text.
     * @return array
     */
    public function read($text)
    {
        $text = str_replace("\r\n", "\n", $text);

        foreach ($this->quote_headers_regex as $regex) {
            if (preg_match($regex, $text, $matches)) {
                $text = str_replace($matches[1], str_replace("\n", ' ', $matches[1]), $text);
            }
        }

        $lines = explode("\n", strrev($text));

        $fragment = null;
        $isQuoted = false;
        $foundVisible = false;

        foreach ($lines as $line) {
            $line = preg_replace("/\n$/", '', $line);

            if (!preg_match(self::SIG_REGEX, $line)) {
                $line = ltrim($line);
            }

            // isQuoted ?
            $isQuoted = preg_match('/(>+)$/s', $line) ? true : false;

            if (null !== $fragment && empty($line)) {
                if (preg_match(self::SIG_REGEX, $fragment->getLastLine())) {
                    $fragment->setIsSignature(true);

                    if (!$foundVisible) {
                        if ($fragment->isQuoted() || $fragment->isSignature() || $fragment->isEmpty()) {
                            $fragment->setIsHidden(true);
                        } else {
                            $foundVisible = true;
                        }
                    }

                    $this->fragments[] = $fragment;
                    $fragment = null;
                }
            }

            if (null !== $fragment &&
                (($isQuoted === $fragment->isQuoted()) ||
                ($fragment->isQuoted() && ($this->isQuoteHeader($line) || empty($line))))
            ) {
                $fragment->addLine($line);
            } else {
                if (null !== $fragment) {
                    if (!$foundVisible) {
                        if ($fragment->isQuoted() || $fragment->isSignature() || $fragment->isEmpty()) {
                            $fragment->setIsHidden(true);
                        } else {
                            $foundVisible = true;
                        }
                    }

                    $this->fragments[] = $fragment;
                }
                $fragment = null;
                $fragment = new Fragment($line, $isQuoted);
            }
        }

        if (null !== $fragment) {
            if (!$foundVisible) {
                if ($fragment->isQuoted() || $fragment->isSignature() || $fragment->isEmpty()) {
                    $fragment->setIsHidden(true);
                } else {
                    $foundVisible = true;
                }
            }

            $this->fragments[] = $fragment;
        }

        $this->fragments = array_reverse($this->fragments);

        return $this->fragments;
    }

    /**
     * Returns an array of fragments.
     *
     * @return array
     */
    public function getFragments()
    {
        return $this->fragments;
    }

    /**
     * @return string
     */
    public function getVisibleText()
    {
        $visibleFragments = array_filter($this->getFragments(), function($fragment) {
            return !$fragment->isHidden();
        });

        return rtrim(implode("\n", $visibleFragments));
    }

    private function isQuoteHeader($line)
    {
        foreach ($this->quote_headers_regex_reverse as $regex) {
            if (preg_match($regex, $line)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns an array of quote header regex.
     *
     * @return array
     */
    public function getQutoteHeaderRegex()
    {
        return $this->quote_headers_regex;
    }
    
    /**
     * Returns an array of quote header reversed regex.
     *
     * @return array
     */
    public function getQutoteHeaderReverseRegex()
    {
        return $this->quote_headers_regex_reverse;
    }
    
    /**
     * Set quote header regex array.
     */
    public function setQutoteHeaderRegex(array $a)
    {
        $this->quote_headers_regex = $a;
    }
    
    /**
     * Set quote header reversed regex array.
     */
    public function setQutoteHeaderReverseRegex(array $a)
    {
        $this->quote_headers_regex_reverse = $a;
    }
}
