EmailReplyParser
================

[![Build Status](https://secure.travis-ci.org/willdurand/EmailReplyParser.png)](http://travis-ci.org/willdurand/EmailReplyParser)

**EmailReplyParser** is a port of the GitHub's [EmailReplyParser](http://github.com/github/email_reply_parser) library written in Ruby.
This is a small PHP 5.3 library to parse plain text email content.


Installation
------------

If you don't use a _ClassLoader_ in your application, just require the provided autoloader:

``` php
<?php

require_once 'src/autoload.php';
```

You're done.


Usage
-----

Instanciate an `Email` object and you're done:

``` php
<?php

$email = new \EmailReplyParser\Email();

$reply = $email->read($emailContent);
// same as:
$reply = $email->getFragments();
```

Alternatively, you can use the static way:

``` php
$reply = \EmailReplyParser\EmailReplyParser::read($emailContent);
```

`$reply` is an array of `Fragment` objects. To get the content of each fragment, just call the `getContent()` method.

A `Fragment` can be a signature, a quoted text, or an hidden text. Here is the API:

``` php
<?php

// Get the content
$fragment->getContent();

// Whether the fragment is a signature or not
$fragment->isSignature();

// Whether the fragment is quoted or not
$fragment->isQuoted();

// Whether the fragment is hidden or not
$fragment->isHidden();

// Whether the fragment is empty or not
$fragment->isEmpty();
```


Known Issues
------------

### Quoted Headers

Quoted headers aren't picked up if there's an extra line break:

    On <date>, <author> wrote:

    > blah

Also, they're not picked up if the email client breaks it up into
multiple lines.  GMail breaks up any lines over 80 characters for you.

    On <date>, <author>
    wrote:
    > blah

Not to mention that we're search for "on" and "wrote".  It won't work
with other languages.

Possible solution: Remove "reply@reply.github.com" lines...

### Weird Signatures

Lines starting with `-` or `_` sometimes mark the beginning of
signatures:

    Hello

    -- 
    Rick

Not everyone follows this convention:

    Hello

    Mr Rick Olson
    Galactic President Superstar Mc Awesomeville
    GitHub

    **********************DISCLAIMER***********************************
    * Note: blah blah blah                                            *
    **********************DISCLAIMER***********************************



### Strange Quoting

Apparently, prefixing lines with `>` isn't universal either:

    Hello

    --
    Rick

    ________________________________________
    From: Bob [reply@reply.github.com]
    Sent: Monday, March 14, 2011 6:16 PM
    To: Rick


Unit Tests
----------

To run unit tests, you'll need a set of dependencies you can install by running the `install_vendors.sh` script:

```
./bin/install_vendors.sh
```

Once installed, just launch the following command:

```
phpunit
```


Credits
-------

* GitHub
* William Durand <william.durand1@gmail.com>


License
-------

EmailReplyParser is released under the MIT License. See the bundled LICENSE file for details.
