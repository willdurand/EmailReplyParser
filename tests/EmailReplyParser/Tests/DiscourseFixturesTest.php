<?php

namespace EmailReplyParser\Tests;

use EmailReplyParser\EmailReplyParser;

class DiscourseFixturesTest extends TestCase
{
    /**
     * @dataProvider provideFilenames
     */
    public function testTrimMatchesFixture($filename)
    {
        $email = $this->getFixtures('discourse_email_reply_trimmer/emails/' . $filename);
        $reply = $this->getFixtures('discourse_email_reply_trimmer/trimmed/' . $filename);

        $this->assertEquals(trim($reply), trim(EmailReplyParser::parseReply($email)));
    }

    public function provideFilenames()
    {
        $files = glob(__DIR__ . "/../../Fixtures/discourse_email_reply_trimmer/emails/*.txt");

        foreach ($files as $file) {
            yield [basename($file)];
        }
    }
}
