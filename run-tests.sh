#!/bin/sh

# DEPENDENCEY
# https://github.com/php/phpruntests

export TEST_PHP_EXECUTABLE=/usr/bin/php
export TEST_PHP_CGI_EXECUTABLE=/usr/bin/php-cgi
$TEST_PHP_EXECUTABLE ~/bin/phpruntests/src/run-tests.php -v tests 

