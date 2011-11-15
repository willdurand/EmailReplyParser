<?php

namespace EmailReplyParser;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class EmailReplyParser
{
    /**
     * @param string $text  An email as text.
     *
     * @return array
     */
    static public function read($text)
    {
        $email = new Email();
        return $email->read($text);
    }
}
