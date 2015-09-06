<?php

require "../src/magic.class.php";

$test = new Priler\Text2Image\Magic(file_get_contents(__FILE__));

$test->text_color = '#C792EA'; // you can use hex
$test->text_color = array(199, 146, 234); // or array of rgb values to set color you want

$test->output('jpg', 75);