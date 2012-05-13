<?php

namespace EmailReplyParser\Tests;

use EmailReplyParser\Email;
use EmailReplyParser\EmailReplyParser;

class EmailTest extends TestCase
{
    protected $email = null;

    protected function setUp()
    {
        $this->email = new Email();
    }

    public function testReadsSimpleBody()
    {
        $reply = $this->email->read(file_get_contents(__DIR__.'/../../Fixtures/email_1.txt'));

        $this->assertEquals(3, count($reply));

        foreach ($reply as $r) {
            $this->assertFalse($r->isQuoted());
        }

        $this->assertFalse($reply[0]->isSignature());
        $this->assertTrue($reply[1]->isSignature());
        $this->assertTrue($reply[2]->isSignature());

        $this->assertFalse($reply[0]->isHidden());
        $this->assertTrue($reply[1]->isHidden());
        $this->assertTrue($reply[2]->isHidden());

        $this->assertEquals("Hi folks

What is the best way to clear a Riak bucket of all key, values after
running a test?
I am currently using the Java HTTP API.\n", (string)$reply[0]);

        $this->assertEquals("-Abhishek Kona\n\n", (string)$reply[1]);
    }

    public function testReadsTopPost()
    {
        $reply = $this->email->read(file_get_contents(__DIR__.'/../../Fixtures/email_3.txt'));

        $this->assertEquals(5, count($reply));

        $this->assertFalse($reply[0]->isQuoted());
        $this->assertFalse($reply[1]->isQuoted());
        $this->assertTrue($reply[2]->isQuoted());
        $this->assertFalse($reply[3]->isQuoted());
        $this->assertFalse($reply[4]->isQuoted());

        $this->assertFalse($reply[0]->isSignature());
        $this->assertTrue($reply[1]->isSignature());
        $this->assertFalse($reply[2]->isSignature());
        $this->assertFalse($reply[3]->isSignature());
        $this->assertTrue($reply[4]->isSignature());

        $this->assertFalse($reply[0]->isHidden());
        $this->assertTrue($reply[1]->isHidden());
        $this->assertTrue($reply[2]->isHidden());
        $this->assertTrue($reply[3]->isHidden());
        $this->assertTrue($reply[4]->isHidden());


        $this->assertRegExp('/^Oh thanks.\n\nHaving/', (string)$reply[0]);
        $this->assertRegExp('/^-A/', (string)$reply[1]);
        $this->assertRegExp('/^On [^\:]+\:/', (string)$reply[2]);
        $this->assertRegExp('/^_/', (string)$reply[4]);
    }

    public function testReadsBottomPost()
    {
        $reply = $this->email->read(file_get_contents(__DIR__.'/../../Fixtures/email_2.txt'));

        $this->assertEquals(6, count($reply));
        $this->assertEquals('Hi,', (string)$reply[0]);
        $this->assertRegExp('/^On [^\:]+\:/', (string)$reply[1]);
        $this->assertRegExp('/^You can list/', (string)$reply[2]);
        $this->assertRegExp('/^>/', (string)$reply[3]);
        $this->assertRegExp('/^_/', (string)$reply[5]);
    }

    public function testRecognizesDateStringAboveQuote()
    {
        $reply = $this->email->read(file_get_contents(__DIR__.'/../../Fixtures/email_4.txt'));

        $this->assertRegExp('/^Awesome/', (string)$reply[0]);
        $this->assertRegExp('/^On/', (string)$reply[1]);
        $this->assertRegExp('/Loader/', (string)$reply[1]);
    }

    public function testDoesNotModifiyInputString()
    {
        $input = 'The Quick Brown Fox Jumps Over The Lazy Dog';
        $this->email->read($input);

        $this->assertEquals('The Quick Brown Fox Jumps Over The Lazy Dog', $input);
    }

    public function testComplexBodyWithOnlyOneFragment()
    {
        $reply = $this->email->read(file_get_contents(__DIR__.'/../../Fixtures/email_5.txt'));

        $this->assertEquals(1, count($reply));
    }

    public function testDealsWithMultilineReplyHeaders()
    {
        $reply = $this->email->read(file_get_contents(__DIR__.'/../../Fixtures/email_6.txt'));

        $this->assertRegExp('/^I get/', (string)$reply[0]);
        $this->assertRegExp('/^On/', (string)$reply[1]);
        $this->assertRegExp('/Was this/', (string)$reply[1]);
    }

    public function testGetVisibleTextReturnsOnlyVisibleFragments()
    {
        $reply = $this->email->read(file_get_contents(__DIR__.'/../../Fixtures/email_2.txt'));
        $visibleFragments = array_filter($reply, function($fragment) {
            return !$fragment->isHidden();
        });

        $this->assertEquals(rtrim(implode("\n", $visibleFragments)), $this->email->getVisibleText());
    }

    public function testParseReply()
    {
        $body = file_get_contents(__DIR__.'/../../Fixtures/email_2.txt');
        $this->email->read($body);

        $this->assertEquals($this->email->getVisibleText(), EmailReplyParser::parseReply($body));
    }

    public function testParseOutSentFromIPhone()
    {
        $body = file_get_contents(__DIR__.'/../../Fixtures/email_iphone.txt');

        $this->assertEquals('Here is another email', EmailReplyParser::parseReply($body));
    }

    public function testParseOutSentFromBlackBerry()
    {
        $body = file_get_contents(__DIR__.'/../../Fixtures/email_blackberry.txt');

        $this->assertEquals('Here is another email', EmailReplyParser::parseReply($body));
    }
}
