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

Then you will have 2 options: build for php and build for python.
First build php version and then build python version. The order is importnant,
because python version requires a php build.

Resulting files are located on root of the project:
EMT.php
EMT.py


RUNNING
=======
Use online.php to test php version of the Typograph.

example.php contains few examples, how to use typograph inside php.
run.py contains examples, how to use typograph inside python.


DEBUGGING
=========
Debug EMT, use debug.php
This script will show all the rules tat were used, for selected text and show step by step log.
