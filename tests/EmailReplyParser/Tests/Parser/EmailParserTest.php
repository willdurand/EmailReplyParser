<?php

namespace EmailReplyParser\Tests\Parser;

use EmailReplyParser\Parser\EmailParser;
use EmailReplyParser\Tests\TestCase;

class EmailParserTest extends TestCase
{
    /**
     * @var EmailParser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new EmailParser();
    }

    public function testReadsSimpleBody()
    {
        $email     = $this->parser->parse($this->getFixtures('email_1.txt'));
        $fragments = $email->getFragments();

        $this->assertCount(3, $fragments);

        foreach ($fragments as $r) {
            $this->assertFalse($r->isQuoted());
        }

        $this->assertFalse($fragments[0]->isSignature());
        $this->assertTrue($fragments[1]->isSignature());
        $this->assertTrue($fragments[2]->isSignature());

        $this->assertFalse($fragments[0]->isHidden());
        $this->assertTrue($fragments[1]->isHidden());
        $this->assertTrue($fragments[2]->isHidden());

        $this->assertEquals(<<<EMAIL
Hi folks

What is the best way to clear a Riak bucket of all key, values after
running a test?
I am currently using the Java HTTP API.

EMAIL
        , (string) $fragments[0]);

        $this->assertEquals("-Abhishek Kona\n\n", (string) $fragments[1]);
    }

    public function testReusesParser()
    {
        $email1 = $this->parser->parse($this->getFixtures('email_1.txt'));
        $this->assertCount(3, $email1->getFragments());

        $email2 = $this->parser->parse($this->getFixtures('email_1.txt'));
        $this->assertCount(3, $email2->getFragments());
    }

    public function testReadsTopPost()
    {
        $email     = $this->parser->parse($this->getFixtures('email_3.txt'));
        $fragments = $email->getFragments();

        $this->assertCount(5, $fragments);

        $this->assertFalse($fragments[0]->isQuoted());
        $this->assertFalse($fragments[1]->isQuoted());
        $this->assertTrue($fragments[2]->isQuoted());
        $this->assertFalse($fragments[3]->isQuoted());
        $this->assertFalse($fragments[4]->isQuoted());

        $this->assertFalse($fragments[0]->isSignature());
        $this->assertTrue($fragments[1]->isSignature());
        $this->assertFalse($fragments[2]->isSignature());
        $this->assertFalse($fragments[3]->isSignature());
        $this->assertTrue($fragments[4]->isSignature());

        $this->assertFalse($fragments[0]->isHidden());
        $this->assertTrue($fragments[1]->isHidden());
        $this->assertTrue($fragments[2]->isHidden());
        $this->assertTrue($fragments[3]->isHidden());
        $this->assertTrue($fragments[4]->isHidden());

        $this->assertRegExp('/^Oh thanks.\n\nHaving/', (string) $fragments[0]);
        $this->assertRegExp('/^-A/', (string) $fragments[1]);
        $this->assertRegExp('/^On [^\:]+\:/', (string) $fragments[2]);
        $this->assertRegExp('/^_/', (string) $fragments[4]);
    }

    public function testReadsBottomPost()
    {
        $email     = $this->parser->parse($this->getFixtures('email_2.txt'));
        $fragments = $email->getFragments();

        $this->assertCount(6, $fragments);
        $this->assertEquals('Hi,', (string) $fragments[0]);
        $this->assertRegExp('/^On [^\:]+\:/', (string) $fragments[1]);
        $this->assertRegExp('/^You can list/', (string) $fragments[2]);
        $this->assertRegExp('/^>/', (string) $fragments[3]);
        $this->assertRegExp('/^_/', (string) $fragments[5]);
    }

    public function testRecognizesDateStringAboveQuote()
    {
        $email     = $this->parser->parse($this->getFixtures('email_4.txt'));
        $fragments = $email->getFragments();

        $this->assertRegExp('/^Awesome/', (string) $fragments[0]);
        $this->assertRegExp('/^On/', (string) $fragments[1]);
        $this->assertRegExp('/Loader/', (string) $fragments[1]);
    }

    public function testDoesNotModifyInputString()
    {
        $input = 'The Quick Brown Fox Jumps Over The Lazy Dog';
        $this->parser->parse($input);

        $this->assertEquals('The Quick Brown Fox Jumps Over The Lazy Dog', $input);
    }

    public function testComplexBodyWithOnlyOneFragment()
    {
        $email = $this->parser->parse($this->getFixtures('email_5.txt'));

        $this->assertCount(1, $email->getFragments());
    }

    public function testDealsWithMultilineReplyHeaders()
    {
        $email     = $this->parser->parse($this->getFixtures('email_6.txt'));
        $fragments = $email->getFragments();

        $this->assertRegExp('/^I get/', (string) $fragments[0]);
        $this->assertRegExp('/^On/', (string) $fragments[1]);
        $this->assertRegExp('/Was this/', (string) $fragments[1]);
    }

    public function testGetVisibleTextReturnsOnlyVisibleFragments()
    {
        $email = $this->parser->parse($this->getFixtures('email_2_1.txt'));
        $visibleFragments = array_filter($email->getFragments(), function ($fragment) {
            return !$fragment->isHidden();
        });

        $this->assertEquals(rtrim(implode("\n", $visibleFragments)), $email->getVisibleText());
    }

    public function testReadsEmailWithCorrectSignature()
    {
        $email     = $this->parser->parse($this->getFixtures('correct_sig.txt'));
        $fragments = $email->getFragments();

        $this->assertCount(2, $fragments);

        $this->assertFalse($fragments[0]->isQuoted());
        $this->assertFalse($fragments[1]->isQuoted());

        $this->assertFalse($fragments[0]->isSignature());
        $this->assertTrue($fragments[1]->isSignature());

        $this->assertFalse($fragments[0]->isHidden());
        $this->assertTrue($fragments[1]->isHidden());

        $this->assertRegExp('/^--\nrick/', (string) $fragments[1]);
    }

    public function testReadsEmailWithSignatureWithNoEmptyLineAbove()
    {
        $email     = $this->parser->parse($this->getFixtures('sig_no_empty_line.txt'));
        $fragments = $email->getFragments();

        $this->assertCount(2, $fragments);

        $this->assertFalse($fragments[0]->isQuoted());
        $this->assertFalse($fragments[1]->isQuoted());

        $this->assertFalse($fragments[0]->isSignature());
        $this->assertTrue($fragments[1]->isSignature());

        $this->assertFalse($fragments[0]->isHidden());
        $this->assertTrue($fragments[1]->isHidden());

        $this->assertRegExp('/^--\nrick/', (string) $fragments[1]);
    }

    public function testReadsEmailWithCorrectSignatureWithSpace()
    {
        // A common convention is to use "-- " as delimitor, but trailing spaces are often stripped by IDEs, so add them here
        $content = str_replace('--', '-- ', $this->getFixtures('correct_sig.txt'));

        $email     = $this->parser->parse($content);
        $fragments = $email->getFragments();

        $this->assertCount(2, $fragments);

        $this->assertFalse($fragments[0]->isQuoted());
        $this->assertFalse($fragments[1]->isQuoted());

        $this->assertFalse($fragments[0]->isSignature());
        $this->assertTrue($fragments[1]->isSignature());

        $this->assertFalse($fragments[0]->isHidden());
        $this->assertTrue($fragments[1]->isHidden());

        $this->assertRegExp('/^-- \nrick/', (string) $fragments[1]);
    }

    public function testReadsEmailWithCorrectSignatureWithNoEmptyLineWithSpace()
    {
        // A common convention is to use "-- " as delimitor, but trailing spaces are often stripped by IDEs, so add them here
        $content = str_replace('--', '-- ', $this->getFixtures('sig_no_empty_line.txt'));

        $email     = $this->parser->parse($content);
        $fragments = $email->getFragments();

        $this->assertCount(2, $fragments);

        $this->assertFalse($fragments[0]->isQuoted());
        $this->assertFalse($fragments[1]->isQuoted());

        $this->assertFalse($fragments[0]->isSignature());
        $this->assertTrue($fragments[1]->isSignature());

        $this->assertFalse($fragments[0]->isHidden());
        $this->assertTrue($fragments[1]->isHidden());

        $this->assertRegExp('/^-- \nrick/', (string) $fragments[1]);
    }

    public function testOneIsNotOn()
    {
        $email     = $this->parser->parse($this->getFixtures('email_one_is_not_on.txt'));
        $fragments = $email->getFragments();

        $this->assertRegExp('/One outstanding question/', (string) $fragments[0]);
        $this->assertRegExp('/^On Oct 1, 2012/', (string) $fragments[1]);
    }

    public function testCustomQuoteHeader()
    {
        $regex   = $this->parser->getQuoteHeadersRegex();
        $regex[] = '/^(\d{4}(.+)rta:)$/ms';
        $this->parser->setQuoteHeadersRegex($regex);

        $email = $this->parser->parse($this->getFixtures('email_custom_quote_header.txt'));

        $this->assertEquals('Thank you!', $email->getVisibleText());
    }

    public function testCustomQuoteHeader2()
    {
        $regex   = $this->parser->getQuoteHeadersRegex();
        $regex[] = '/^(From\: .+ .+test\@webdomain\.com.+)/ms';
        $this->parser->setQuoteHeadersRegex($regex);

        $email = $this->parser->parse($this->getFixtures('email_customer_quote_header_2.txt'));
        $fragments = $email->getFragments();
        $this->assertCount(2, $fragments);

        $this->assertEquals('Thank you very much.', $email->getVisibleText());
        $this->assertTrue($fragments[1]->isHidden());
        $this->assertTrue($fragments[1]->isQuoted());
    }

    /**
     * @dataProvider getDateFormats
     */
    public function testDateQuoteHeader($date)
    {
        $email = $this->parser->parse(str_replace('[DATE]', $date, $this->getFixtures('email_with_date_headers.txt')));

        $this->assertEquals('Thank you very much.', $email->getVisibleText());
    }

    public function getDateFormats()
    {
        return array(
            array('On Tue, 2011-03-01 at 18:02 +0530, Abhishek Kona wrote:'),
            array('2014-03-20 8:48 GMT+01:00 Rémi Dolan <do_not_reply@dolan.com>:'), // Gmail
            array('2014-03-20 20:48 GMT+01:00 Rémi Dolan <do_not_reply@dolan.com>:'), // Gmail
            array('2014-03-09 20:48 GMT+01:00 Rémi Dolan <do_not_reply@dolan.com>:'), // Gmail
            array('Le 19 mars 2014 10:37, Cédric Lombardot <cedric.lombardot@gmail.com> a écrit :'), // Gmail
            array('El 19/03/2014 11:34, Juan Pérez <juan.perez@mailcatch.com> escribió:'), // Gmail in spanish
            array('W dniu 7 stycznia 2015 15:24 użytkownik Paweł Brzoski <pbrzoski91@gmail.com> napisał:'), //Gmail in polish
            array('Le 19/03/2014 11:34, Georges du chemin a écrit :'), // Thunderbird
            array('W dniu 2015-01-07 14:23, pbrzoski91@gmail.com pisze: '), // Thunderbird in polish
            array('Den 08/06/2015 kl. 21.21 skrev Test user <test@example.com>:'), // Danish
            array('Am 25.06.2015 um 10:55 schrieb Test user:'), // German 1
            array('Test user <test@example.com> schrieb:'), // German 2
        );
    }
}
