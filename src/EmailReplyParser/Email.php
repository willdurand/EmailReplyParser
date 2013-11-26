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
    const SIG_REGEX = '/(^--|^__|\w-$)|(^(\w+\s*){1,3} ym morf tneS$)/s';

    protected $quoteHeadersRegex = array(
        '/^(On\s(.+)wrote:)$/ms', // On DATE, NAME <EMAIL> wrote:
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

        foreach ($this->quoteHeadersRegex as $regex) {
            if (preg_match($regex, $text, $matches)) {
                $text = str_replace($matches[1], str_replace("\n", ' ', $matches[1]), $text);
            }
        }

        $lines = explode("\n", strrev($text));

        $fragment = null;

        foreach ($lines as $line) {
            $line = rtrim($line, "\n");

            if (!$this->isSignature($line)) {
                $line = ltrim($line);
            }

            if ($fragment && empty($line)) {
                $last = $fragment->getLastLine();

                if ($this->isSignature($last)) {
                    $fragment->setIsSignature(true);
                    $this->addFragment($fragment);
                } else if ($this->isQuoteHeader($last)) {
                    $fragment->setIsQuoted(true);
                    $this->addFragment($fragment);
                    // Discard the trailing empty line from the next fragment.
                    // It's arguable whether this is desired behavior but presently
                    // the EmailTest::testCustomQuoteHeader test expects this behavior.
                    continue;
                }
            }

            $isQuoted = preg_match('/(>+)$/s', $line) ? true : false;

            if (!$this->isFragmentLine($fragment, $line, $isQuoted)) {
                $this->addFragment($fragment);
                $fragment = new Fragment($isQuoted);
            }

            $fragment->addLine($line);
        }

        $this->addFragment($fragment);

        $this->fragments = array_reverse($this->fragments);

        return $this->fragments;
    }

    private function isFragmentLine($fragment, $line, $isQuoted) {
        if (!$fragment) {
            return false;
        }

        return ($fragment->isQuoted() === $isQuoted)
            || ($fragment->isQuoted() && ($this->isQuoteHeader($line) || empty($line)));
    }

    private function addFragment(&$fragment)
    {
        if ($fragment) {
            if ($fragment->isQuoted() || $fragment->isSignature() || $fragment->isEmpty()) {
                $fragment->setIsHidden(true);
            }
            $this->fragments[] = $fragment;
        }
        $fragment = null;
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

    /**
     * @return array
     */
    public function getQuoteHeadersRegex()
    {
        return $this->quoteHeadersRegex;
    }

    public function setQuoteHeadersRegex(array $quoteHeadersRegex)
    {
        $this->quoteHeadersRegex = $quoteHeadersRegex;
    }

    private function isQuoteHeader($line)
    {
        foreach ($this->quoteHeadersRegex as $regex) {
            if (preg_match($regex, strrev($line))) {
                return true;
            }
        }

        return false;
    }

    private function isSignature($line)
    {
        return preg_match(self::SIG_REGEX, $line);
    }
}
