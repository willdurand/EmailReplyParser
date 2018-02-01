<?php

/**
 * This file is part of the EmailReplyParser package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace EmailReplyParser\Parser;

use EmailReplyParser\Email;
use EmailReplyParser\Fragment;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class EmailParser
{
    const QUOTE_REGEX = '/>+$/s';

    /**
     * Regex to match signatures
     *
     * @var string
     */
    private $signatureRegex = '/(?:^\s*--|^\s*__|^-\w|^-- $)|(?:^Sent from my (?:\s*\w+){1,4}$)|(?:^={30,}$)$/s';

    /**
     * @var string[]
     */
    private $quoteHeadersRegex = array(
        '/^\s*(On(?:(?!^>*\s*On\b|\bwrote:).){0,1000}wrote:)$/ms', // On DATE, NAME <EMAIL> wrote:
        '/^\s*(Le(?:(?!^>*\s*Le\b|\bécrit:).){0,1000}écrit :)$/ms', // Le DATE, NAME <EMAIL> a écrit :
        '/^\s*(El(?:(?!^>*\s*El\b|\bescribió:).){0,1000}escribió:)$/ms', // El DATE, NAME <EMAIL> escribió:
        '/^\s*(Il(?:(?!^>*\s*Il\b|\bscritto:).){0,1000}scritto:)$/ms', // Il DATE, NAME <EMAIL> ha scritto:
        '/^[\S\s]+ (написа(л|ла|в)+)+:$/msu', // Everything before написал: not ending on wrote:
        '/^\s*(Op\s.+?schreef.+:)$/ms', // Il DATE, schreef NAME <EMAIL>:
        '/^\s*((W\sdniu|Dnia)\s.+?(pisze|napisał(\(a\))?):)$/msu', // W dniu DATE, NAME <EMAIL> pisze|napisał:
        '/^\s*(Den\s.+\sskrev\s.+:)$/m', // Den DATE skrev NAME <EMAIL>:
        '/^\s*(Am\s.+\sum\s.+\sschrieb\s.+:)$/m', // Am DATE um TIME schrieb NAME:
        '/^(在.+写道：)$/ms', // > 在 DATE, TIME, NAME 写道：
        '/^(20[0-9]{2}\..+\s작성:)$/m', // DATE TIME NAME 작성:
        '/^(20[0-9]{2}\/.+のメッセージ:)$/m', // DATE TIME、NAME のメッセージ:
        '/^(.+\s<.+>\sschrieb:)$/m', // NAME <EMAIL> schrieb:
        '/^\s*(From\s?:.+\s?(\[|<).+(\]|>))/mu', // "From: NAME <EMAIL>" OR "From : NAME <EMAIL>" OR "From : NAME<EMAIL>"(With support whitespace before start and before <)
        '/^\s*(发件人\s?:.+\s?(\[|<).+(\]|>))/mu', // "发件人: NAME <EMAIL>" OR "发件人 : NAME <EMAIL>" OR "发件人 : NAME<EMAIL>"(With support whitespace before start and before <)
        '/^\s*(De\s?:.+\s?(\[|<).+(\]|>))/mu', // "De: NAME <EMAIL>" OR "De : NAME <EMAIL>" OR "De : NAME<EMAIL>"  (With support whitespace before start and before <)
        '/^\s*(Van\s?:.+\s?(\[|<).+(\]|>))/mu', // "Van: NAME <EMAIL>" OR "Van : NAME <EMAIL>" OR "Van : NAME<EMAIL>"  (With support whitespace before start and before <)
        '/^\s*(Da\s?:.+\s?(\[|<).+(\]|>))/mu', // "Da: NAME <EMAIL>" OR "Da : NAME <EMAIL>" OR "Da : NAME<EMAIL>"  (With support whitespace before start and before <)
        '/^(20[0-9]{2}\-(?:0?[1-9]|1[012])\-(?:0?[0-9]|[1-2][0-9]|3[01]|[1-9])\s[0-2]?[0-9]:\d{2}\s.+?:)$/ms', // 20YY-MM-DD HH:II GMT+01:00 NAME <EMAIL>:
        '/^\s*([a-z]{3,4}\.\s.+\sskrev\s.+:)$/ms', // DATE skrev NAME <EMAIL>:
    );

    /**
     * @var FragmentDTO[]
     */
    private $fragments = array();

    /**
     * Parse a text which represents an email and splits it into fragments.
     *
     * @param string $text A text.
     *
     * @return Email
     */
    public function parse($text)
    {
        $text = str_replace("\r\n", "\n", $text);

        foreach ($this->quoteHeadersRegex as $regex) {
            if (preg_match($regex, $text, $matches)) {
                $text = str_replace($matches[1], str_replace("\n", ' ', $matches[1]), $text);
            }
        }

        $fragment = null;
        foreach (explode("\n", strrev($text)) as $line) {
            $line = rtrim($line, "\n");

            if (!$this->isSignature($line)) {
                $line = ltrim($line);
            }

            if ($fragment) {
                $last = end($fragment->lines);

                if ($this->isSignature($last)) {
                    $fragment->isSignature = true;
                    $this->addFragment($fragment);

                    $fragment = null;
                } elseif (empty($line) && $this->isQuoteHeader($last)) {
                    $fragment->isQuoted = true;
                    $this->addFragment($fragment);

                    $fragment = null;
                }
            }

            $isQuoted = $this->isQuote($line);

            if (null === $fragment || !$this->isFragmentLine($fragment, $line, $isQuoted)) {
                if ($fragment) {
                    $this->addFragment($fragment);
                }

                $fragment = new FragmentDTO();
                $fragment->isQuoted = $isQuoted;
            }

            $fragment->lines[] = $line;
        }

        if ($fragment) {
            $this->addFragment($fragment);
        }

        $email = $this->createEmail($this->fragments);

        $this->fragments = array();

        return $email;
    }

    /**
     * @return string[]
     */
    public function getQuoteHeadersRegex()
    {
        return $this->quoteHeadersRegex;
    }

    /**
     * @param string[] $quoteHeadersRegex
     *
     * @return EmailParser
     */
    public function setQuoteHeadersRegex(array $quoteHeadersRegex)
    {
        $this->quoteHeadersRegex = $quoteHeadersRegex;

        return $this;
    }

    /**
     * @return string
     * @since 2.7.0
     */
    public function getSignatureRegex()
    {
        return $this->signatureRegex;
    }

    /**
     * @param string $signatureRegex
     *
     * @return EmailParser
     * @since 2.7.0
     */
    public function setSignatureRegex($signatureRegex)
    {
        $this->signatureRegex = $signatureRegex;

        return $this;
    }

    /**
     * @param FragmentDTO[] $fragmentDTOs
     *
     * @return Email
     */
    protected function createEmail(array $fragmentDTOs)
    {
        $fragments = array();
        foreach (array_reverse($fragmentDTOs) as $fragment) {
            $fragments[] = new Fragment(
                preg_replace("/^\n/", '', strrev(implode("\n", $fragment->lines))),
                $fragment->isHidden,
                $fragment->isSignature,
                $fragment->isQuoted
            );
        }

        return new Email($fragments);
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
        return preg_match($this->signatureRegex, strrev($line)) ? true : false;
    }

    /**
     * @param string $line
     * @return bool
     */
    private function isQuote($line)
    {
        return preg_match(static::QUOTE_REGEX, $line) ? true : false;
    }

    private function isEmpty(FragmentDTO $fragment)
    {
        return '' === implode('', $fragment->lines);
    }

    /**
     * @param FragmentDTO $fragment
     * @param string  $line
     * @param boolean $isQuoted
     * @return bool
     */
    private function isFragmentLine(FragmentDTO $fragment, $line, $isQuoted)
    {
        return $fragment->isQuoted === $isQuoted ||
            ($fragment->isQuoted && ($this->isQuoteHeader($line) || empty($line)));
    }

    private function addFragment(FragmentDTO $fragment)
    {
        if ($fragment->isQuoted || $fragment->isSignature || $this->isEmpty($fragment)) {
            $fragment->isHidden = true;
        }

        $this->fragments[] = $fragment;
    }
}
