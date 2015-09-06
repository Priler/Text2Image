<?php

require "../src/magic.class.php";

$test = new Priler\Text2Image\Magic('Hello world!');
$test->output();