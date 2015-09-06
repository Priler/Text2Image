<?php

require "../src/magic.class.php";

$test = new Priler\Text2Image\Magic(
'Hello world!
This cool image was saved to file with JPEG quality equal to 75.

Other formats, like GIF, PNG and WBMP are supported too.
In JPG quality can be between 0 (worst) and 100 (highest quality), of course it makes sense on output file size.
In PNG quality means the compression rate (between 0 and 9).

Also, you might wanna check if you\'r PHP build support\'s required image type or not.
You can do this easily using method called is_imagetype_supported($type) on any instance of Priler\Text2Image\Magic.

Thanks.');
$test->save('6_Save_Into_File_OUTPUT.jpg', 'jpg', 75);

echo 'Saved!';