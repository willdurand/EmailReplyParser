<?php

namespace EmailReplyParser\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $file
     */
    protected function getFixtures($file)
    {
        return file_get_contents(__DIR__ . '/../../Fixtures/' . $file);
    }
}
