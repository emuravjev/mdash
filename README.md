mdash
=====

Evgeny Muravjev Typograph, http://mdash.ru
Authors: Evgeny Muravjev & Alexander Drutsa  

EMT - Evgeny Muravjev Typograph

BUILDING
========
To build EMT you need php version 5.2 or newer.
Suggested way to build typograph is to run builder.php through web-server.

E.g.:
http://localhost/mdash/builder/builder.php

Then you will have 3 options: build for php, build for python, build tests for python. 

Resulting files are located on root of the project:
EMT.php
EMT.py


RUNNING
=======
Use tools-php/online.php to test php version of the Typograph.

tools-php/example.php contains few examples, how to use typograph inside php.
tools-py/run.py contains examples, how to use typograph inside python.

TESTING
=======
To run tests for php version you should go to the page tools-php/test.php through web-server.
On this page you hit start to run all the tests.

E.g.:
http://localhost/mdash/tools-php/test.php

To python tests you need first build test set from php version through builder/builder.php, and
then run the tests:
cd tools-py
python test.py

DEBUGGING
=========
Debug EMT, use debug.php
This script will show all the rules tat were used, for selected text and show step by step log.
