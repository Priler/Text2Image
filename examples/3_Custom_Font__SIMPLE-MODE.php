<?php

require "../src/magic.class.php";

$test = new Priler\Text2Image\Magic(
"
Lorem Ipsum is simply dummy text of the printing and typesetting industry.
Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.
"
);

// load custom font
// simple-mode work's with only GDF font's
$test->add_font('MyFont', './assets/foughtknight.gdf', 1); // 1 at end forces mode into simple
$test->font = $test->get_font('MyFont');

$test->width = 720; // custom width
$test->background_color = '#263238'; // custom background color
$test->text_color = '#eee'; // custom text color
$test->line_height = 30; // custom line height
$test->padding = 50; // custom padding


$test->output();