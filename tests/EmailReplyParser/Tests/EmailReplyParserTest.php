<?php

namespace EmailReplyParser\Tests;

use EmailReplyParser\Email;
use EmailReplyParser\EmailReplyParser;

class EmailReplyParserTest extends TestCase
{
    protected $email = null;

    protected function setUp()
    {
        $this->email = new Email();
    }

    public function testReadWithNullContent()
    {
        $reply = EmailReplyParser::read(null);

        $this->assertTrue(is_array($reply));
        $this->assertEquals(1, count($reply));
        $this->assertEmpty($reply[0]->__toString());
    }

    public function testReadWithEmptyContent()
    {
        $reply = EmailReplyParser::read('');

        $this->assertTrue(is_array($reply));
        $this->assertEquals(1, count($reply));
        $this->assertEmpty($reply[0]->__toString());
    }

    public function testParseReply()
    {
        $body = $this->getFixtures('email_2.txt');
        $this->email->read($body);

        $this->assertEquals($this->email->getVisibleText(), EmailReplyParser::parseReply($body));
    }

    public function testParseOutSentFromIPhone()
    {
        $body = $this->getFixtures('email_iphone.txt');

        $this->assertEquals('Here is another email', EmailReplyParser::parseReply($body));
    }

    public function testParseOutSentFromBlackBerry()
    {
        $body = $this->getFixtures('email_blackberry.txt');

        $this->assertEquals('Here is another email', EmailReplyParser::parseReply($body));
    }

    public function testParseOutSendFromMultiwordMobileDevice()
    {
        $body = $this->getFixtures('email_multi_word_sent_from_my_mobile_device.txt');

        $this->assertEquals('Here is another email', EmailReplyParser::parseReply($body));
    }

    public function testDoNotParseOutSendFromInRegularSentence()
    {
        $body = $this->getFixtures('email_sent_from_my_not_signature.txt');

        $this->assertEquals(
            "Here is another email\n\nSent from my desk, is much easier then my mobile phone.",
            EmailReplyParser::parseReply($body)
        );
    }

    public function testParseOutJustTopForOutlookReply()
    {
        $body = $this->getFixtures('email_2_1.txt');

        $this->assertEquals('Outlook with a reply', EmailReplyParser::parseReply($body));
    }

    public function testRetainsBullets()
    {
        $body = $this->getFixtures('email_bullets.txt');

        $this->assertEquals(
            "test 2 this should list second\n\nand have spaces\n\nand retain this formatting\n\n\n   - how about bullets\n   - and another",
            EmailReplyParser::parseReply($body)
        );
    }
}
