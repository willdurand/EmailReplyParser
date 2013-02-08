<?php

namespace EmailReplyParser\Tests;

use EmailReplyParser\Email;

class EmailTest extends TestCase
{
    protected $email = null;

    protected function setUp()
    {
        $this->email = new Email();
    }

    public function testReadsSimpleBody()
    {
        $reply = $this->email->read($this->getFixtures('email_1.txt'));

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
I am currently using the Java HTTP API.\n", (string) $reply[0]);

        $this->assertEquals("-Abhishek Kona\n\n", (string) $reply[1]);
    }

    public function testReadsTopPost()
    {
        $reply = $this->email->read($this->getFixtures('email_3.txt'));

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

        $this->assertRegExp('/^Oh thanks.\n\nHaving/', (string) $reply[0]);
        $this->assertRegExp('/^-A/', (string) $reply[1]);
        $this->assertRegExp('/^On [^\:]+\:/', (string) $reply[2]);
        $this->assertRegExp('/^_/', (string) $reply[4]);
    }

    public function testReadsBottomPost()
    {
        $reply = $this->email->read($this->getFixtures('email_2.txt'));

        $this->assertEquals(6, count($reply));
        $this->assertEquals('Hi,', (string) $reply[0]);
        $this->assertRegExp('/^On [^\:]+\:/', (string) $reply[1]);
        $this->assertRegExp('/^You can list/', (string) $reply[2]);
        $this->assertRegExp('/^>/', (string) $reply[3]);
        $this->assertRegExp('/^_/', (string) $reply[5]);
    }

    public function testRecognizesDateStringAboveQuote()
    {
        $reply = $this->email->read($this->getFixtures('email_4.txt'));

        $this->assertRegExp('/^Awesome/', (string) $reply[0]);
        $this->assertRegExp('/^On/', (string) $reply[1]);
        $this->assertRegExp('/Loader/', (string) $reply[1]);
    }

    public function testDoesNotModifiyInputString()
    {
        $input = 'The Quick Brown Fox Jumps Over The Lazy Dog';
        $this->email->read($input);

        $this->assertEquals('The Quick Brown Fox Jumps Over The Lazy Dog', $input);
    }

    public function testComplexBodyWithOnlyOneFragment()
    {
        $reply = $this->email->read($this->getFixtures('email_5.txt'));

        $this->assertEquals(1, count($reply));
    }

    public function testDealsWithMultilineReplyHeaders()
    {
        $reply = $this->email->read($this->getFixtures('email_6.txt'));

        $this->assertRegExp('/^I get/', (string) $reply[0]);
        $this->assertRegExp('/^On/', (string) $reply[1]);
        $this->assertRegExp('/Was this/', (string) $reply[1]);
    }

    public function testGetVisibleTextReturnsOnlyVisibleFragments()
    {
        $reply = $this->email->read($this->getFixtures('email_2_1.txt'));
        $visibleFragments = array_filter($reply, function($fragment) {
            return !$fragment->isHidden();
        });

        $this->assertEquals(rtrim(implode("\n", $visibleFragments)), $this->email->getVisibleText());
    }

    public function testReadsEmailWithCorrectSignature()
    {
        $reply = $this->email->read($this->getFixtures('correct_sig.txt'));

        $this->assertCount(2, $reply);

        $this->assertFalse($reply[0]->isQuoted());
        $this->assertFalse($reply[1]->isQuoted());

        $this->assertFalse($reply[0]->isSignature());
        $this->assertTrue($reply[1]->isSignature());

        $this->assertFalse($reply[0]->isHidden());
        $this->assertTrue($reply[1]->isHidden());

        $this->assertRegExp("/^--\nrick/", (string) $reply[1]);
    }

    public function testOneIsNotOn()
    {
        $reply = $this->email->read($this->getFixtures('email_one_is_not_on.txt'));

        $this->assertRegExp('/One outstanding question/', (string) $reply[0]);
        $this->assertRegExp('/^On Oct 1, 2012/', (string) $reply[1]);
    }

    public function testCustomQuoteHeader()
    {
        $_email = clone $this->email;
        $_email::$quote_headers_regex[] = '/^(\d{4}(.+)rta:)$/ms';

        $reply = $_email->read($this->getFixtures('email_custom_quote_header.txt'));

        $this->assertRegExp('/Thank you!/', (string) $reply[0]);
    }

    public function testEncoding()
    {
        $_email = clone $this->email;
        $_email::$quote_headers_regex[] = '/^(\d{4}(.+)rta:)$/ms';
        $_email::$quote_headers_regex_reverse[] = '/^:atr.*\d{4}$/s';

        $reply = $_email->read($this->getFixtures('email_encoding_iso_8859_2.txt'));

        $this->assertRegExp('/Örülök neki, köszönöm!/', $reply[0]->getContent('ISO-8859-2'));
    }
}
