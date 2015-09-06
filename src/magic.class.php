<?php
/**
 * The most useful & easy2use PHP liblary for converting any text into image
 *
 * @license MIT
 */
namespace Priler\Text2Image;

class Magic {

    const AUTHOR = 'Priler';
    const LIB_NAME = 'Text2Image';
    const VERSION = 1.0;
    const LIFE_CYRCLE = 'Beta';
    const LICENSE = 'MIT';

    /* # Public var's, editable by user */
    public
        $width = 720, // Width of image box, text will be wrapped withing this box [int]
        $font = 5, // Font name/family, can be integer font index for Simple mode, or path to TrueType font for Smart mode [int] or [string]
        $line_height = 'auto', // Height between lines, can be integer or 'auto' [int] or [string]
        $background_color = array(38, 50, 56), // Color of background, can be array of RGB values, or a hex string [array] or [string]
        $text_color = array(255, 255, 255), // Color of text, can be array of RGB values, or a hex string [array] or [string]
        $padding = 30, // Padding by all side's [int]
        $angle = 0, // smart-only Text angle [int]
        $text_size = 17, // smart-only Font size [int]
        $user_fonts = array(); // User defined font's

    /* # Protected var's, inclass usage */
    protected
        $image, // Image resource
        $is_simple = true, // Switcher between modes, with is Simple or Smart
        $offset_x = 0, // Text offset by x-horizontal (LTR - left to right)
        $offset_y = 0, // Text offset by y-vertical (TTB - top to bottom)
        $pseudo_width, // Text box real width, counted with usage of padding
        $height, // Height of result image
        $characters_per_line, // simple-only How many characters can be placed per line
        $text, // Source text, raw
        $lines, // Array filled up with ready4draw text lines
        $pallete; // Pallete for rendering, kind of buffer ... 

    /* # Implementation */

    // @Constructor
    public function __construct($text = '') {
        $this->text = (string)$text;
    }

    // @Definition's maker
    protected function make_definitions() {
        $this->pseudo_width = $this->width;

        if ($this->padding)
        {
            $this->pseudo_width -= $this->padding * 2;
            $this->offset_x = $this->padding;
            if ($this->is_simple)
                $this->offset_y = $this->padding;
            else
                $this->offset_y = $this->text_size + $this->padding;
        }

        if ($this->line_height == 'auto')
        {
            if ($this->is_simple)
                $this->line_height = imagefontheight($this->font) * 1.5;
            else
                $this->line_height = $this->text_size * 1.5;
        }

        if ($this->is_simple)
            $this->characters_per_line = floor($this->pseudo_width / imagefontwidth($this->font));
    }

    // @Text parser
    protected function parse_text() {

        // this will define all required stuff for further code
        $this->make_definitions();
        $this->lines = array();

        //this will parse text, in short : it will define words in lines, in other word's it will wrap words to make them fit into text box (pseudo_width)
        if (!empty($this->text)) {
            $source_lines = preg_split("#(?:\r)?\n#", $this->text, -1);

            if ($this->is_simple) {
                // parse as simple one, no TrueType, no dimensions, just simple bitmaps of GDF :)
                // this is lot faster than smart mode
                // word-wrapping here is based on $characters_per_line, with is defines how many characters will fit in one line
                foreach ($source_lines as $line) {
                    $line = trim($line);
                    // simple-case
                    // if line is not fiiting into text-box, then ...
                    if (mb_strlen($line, 'utf-8') > $this->characters_per_line) {
                        // words separation
                        $source_words = preg_split('#(\s+)#', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
                        $words = array();

                        // @too long words protection (words that is not separated with space, but they can be too long for fitting into text box)
                        for ($j = 0, $wc = count($source_words); $j < $wc; $j++) {
                            // protection from memory lick
                            if (mb_strlen($source_words[$j], 'utf-8') > $this->characters_per_line) {
                                // slice them up, guys ...
                                while ( $slice = mb_substr($source_words[$j], 0, $this->characters_per_line, 'utf-8') ) {
                                    $source_words[$j] = mb_substr($source_words[$j], $this->characters_per_line, null, 'utf-8');
                                    $words[] = $slice;
                                }
                            } else {
                                // direct shifting will break keys nodes, so let's make it the old way
                                $words[] = $source_words[$j];
                                unset($source_words[$j]);
                            }
                        }
                        unset($source_words);// unrequired stuff, let's keep thing's simple and clean chunk faster

                        // @lines array creation, this is where word-wrapping happen's exactly
                        while ($words) {
                            //while there is still words left ...
                            $sentence = '';

                            // keep looping, while there is some place for more characters ...
                            while ( (mb_strlen($sentence, 'utf-8') < $this->characters_per_line) AND ($words) ) {
                                $old_sentence = $sentence;
                                $new_word = array_shift($words);
                                $sentence .= $new_word;
                            }

                            // unshift, if overflow
                            if (mb_strlen($sentence, 'utf-8') > $this->characters_per_line) {
                                $sentence = $old_sentence;
                                array_unshift($words, $new_word);
                            }

                            // and ... append ready sentence into $lines array
                            $this->lines[] = $sentence;

                            // clean some chunk faster, odd, but who care
                            unset($sentence);
                            unset($old_sentence);
                            unset($new_word);
                        }
                    } else {
                        // simple-case
                        // and if line is fitting into text-box, then ..
                        $this->lines[] = $line;
                    }
                }
            } else {
                // parse as smart one, + TrueType, + dimensions, + support of any .TTF font's
                // this is slower than simple mode
                // word-wrapping here is based on $dimensions (getting of this is covered in @text_box_width function), with is defines real width of output text
                foreach ($source_lines as $line) {
                    $line = trim($line);

                    //smart-case
                    // if line is not fiiting into text-box, then ...
                    if ($this->text_box_width($line) > $this->pseudo_width) {
                        // words separation
                        $source_words = preg_split('#(\s+)#', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
                        $words = array();

                        // @too long words protection (words that is not separated with space, but they can be too long for fitting into text box)
                        for ($j = 0, $wc = count($source_words); $j < $wc; $j++) {
                            if ($this->text_box_width($source_words[$j]) > $this->pseudo_width) {
                                // slice them up, guys ...

                                // for faster definition of how much words got to be pushed into $sentence, let's define approximation based on $text_size
                                $approximate_letters_count_in_slice = floor($this->pseudo_width / $this->text_size);

                                while ($source_words[$j]) {
                                    //if cases works as swapping-case
                                    $slice = mb_substr($source_words[$j], 0, $approximate_letters_count_in_slice, 'utf-8');
                                    $source_words[$j] = mb_substr($source_words[$j], $approximate_letters_count_in_slice, null, 'utf-8');
                                    if ( $this->text_box_width($slice) > $this->pseudo_width ) {
                                        // too much, cut ...
                                        while ( ($this->text_box_width($slice . 'a') > $this->pseudo_width) AND ($slice) AND ($source_words[$j]) ) {
                                            $index = (mb_strlen($slice, 'utf-8') - 1);
                                            $source_words[$j] .= $slice[$index];//return letter
                                            $slice = mb_substr($slice, 0, -1, 'utf-8');//recreate slice
                                        }
                                    } else {
                                        // not enought, add ...
                                        while ( ($this->text_box_width($slice . 'a') < $this->pseudo_width) AND ($slice) AND ($source_words[$j]) ) {
                                            $index = (mb_strlen($source_words[$j], 'utf-8') - 1);
                                            $slice .= $source_words[$j][$index];//add letter
                                            $source_words[$j] = mb_substr($source_words[$j], 0, -1, 'utf-8');//cut letter
                                        }
                                    }
                                    $words[] = $slice;
                                }
                                unset($source_words[$j]);
                            } else {
                                // direct shifting will break key nodes, so let's make it the old way
                                $words[] = $source_words[$j];
                                unset($source_words[$j]);
                            }
                        }
                        unset($source_words);

                        // @lines array creation, this is where word-wrapping happen's exactly
                        while ($words) {
                            //while there is still words left ...
                            $sentence = '';

                            // keep looping, while there is some place for more characters ...
                            while ( ($this->text_box_width($sentence) < $this->pseudo_width) AND ($words) ) {
                                $old_sentence = $sentence;
                                $new_word = array_shift($words);
                                $sentence .= $new_word;
                            }

                            // unshift, if overflow
                            if ($this->text_box_width($sentence) > $this->pseudo_width) {
                                $sentence = $old_sentence;
                                array_unshift($words, $new_word);
                            }

                            // and ... append ready sentence into $lines array
                            $this->lines[] = $sentence;

                            // clean some chunk faster, odd, but who care
                            unset($sentence);
                            unset($old_sentence);
                            unset($new_word);
                        }
                    } else {
                        // smart-case
                        // and if line is fitting into text-box, then ..
                        $this->lines[] = $line;
                    }
                }
            }
        }
    }

    // @Text box width
    protected function text_box_width($str) {
        $dimensions = imagettfbbox($this->text_size, $this->angle, $this->font, $str);
        return $dimensions['2'];
    }

    // @hex2rgb converter
    protected function hex2rgb($hex) {
       $hex = str_replace("#", "", $hex);

       if(strlen($hex) == 3) {
          $r = hexdec(substr($hex,0,1).substr($hex,0,1));
          $g = hexdec(substr($hex,1,1).substr($hex,1,1));
          $b = hexdec(substr($hex,2,1).substr($hex,2,1));
       } else {
          $r = hexdec(substr($hex,0,2));
          $g = hexdec(substr($hex,2,2));
          $b = hexdec(substr($hex,4,2));
       }
       $rgb = array($r, $g, $b);
       return $rgb; // returns an array with the rgb values
    }

    protected function render() {
        // pre-requipment's
        $this->parse_text();
        $this->height = count($this->lines) * $this->line_height;
        if ($this->padding)
            $this->height += $this->padding * 2;
        if (!is_array($this->background_color))
            $this->background_color = $this->hex2rgb($this->background_color);
        if (!is_array($this->text_color))
            $this->text_color = $this->hex2rgb($this->text_color);

        // canvas creation
        $this->image = imagecreate($this->width, $this->height);
        $this->pallete = array(
            'background' => imagecolorallocate($this->image, $this->background_color['0'], $this->background_color['1'], $this->background_color['2']),
            'text' => imagecolorallocate($this->image, $this->text_color['0'], $this->text_color['1'], $this->text_color['2'])
        );

        // drawing's creation
        $offset_y__active = $this->offset_y;
        foreach ($this->lines as $line) {
            $line = trim($line);

            if ($this->is_simple)
                imagestring ($this->image, $this->font, $this->offset_x, $offset_y__active, $line, $this->pallete['text']);
            else
                imagettftext($this->image, $this->text_size, $this->angle, $this->offset_x, $offset_y__active, $this->pallete['text'], $this->font, $line);

            $offset_y__active += $this->line_height;
        }

    }

    // @getter for mode
    public function get_mode() {
        return $this->is_simple;
    }

    // @setter for mode
    public function set_mode($new_mode = true) {
        if (is_string($new_mode)) {
            if ($new_mode == 'simple')
                $this->is_simple = true;
            else
                $this->is_simple = false;
        } else
            $this->is_simple = true;
    }

    // @getter for text
    public function get_text() {
        return $this->text;
    }

    // @setter for text
    public function set_text($new_text = '') {
        $this->text = $new_text;
    }

    // @adder for user defined font's
    public function add_font($font_label, $font_path, $force_mode = null) {
        $mode = $this->is_simple;
        if (!is_null($force_mode))
            $mode = $force_mode;

        if ($mode == true) {
            // simple-font, gdf-only
            $this->user_fonts[$font_label] = imageloadfont($font_path);
        } else {
            // smart-font, ttf is recommended
            $this->user_fonts[$font_label] = $font_path;
        }
    }

    // @getter for user defined font's
    public function get_font($font_label) {
        if (isset($this->user_fonts[$font_label]))
            return $this->user_fonts[$font_label];
        else
            return false;
    }

    // @Support check
    public function is_supported() {
        if (extension_loaded('gd'))
            return true;
        else
            return false;
    }

    // @Typetypes support check
    public function is_imagetype_supported($type) {
        switch (strtolower($type)) {
            case 'gif'; return imagetypes() & IMG_GIF; break;
            case 'png'; return imagetypes() & IMG_PNG; break;
            case 'jpg'; return imagetypes() & IMG_JPG; break;
            case 'wbmp'; return imagetypes() & IMG_WBMP; break;
        }
        return false;
    }

    // @output result image into browser
    public function output($type = 'png', $quality = 100) {
        $this->render();

        header("Content-type: image/png");
        switch (strtolower($type)) {
            case 'gif'; imagegif($this->image, null); break;
            case 'png'; imagepng($this->image, null, ($quality > 9) ? 9 : $quality ); break;
            case 'jpg'; imagejpeg($this->image, null, ($quality > 100) ? 100 : $quality ); break;
            case 'wbmp'; imagewbmp($this->image, null, $this->pallete['background']); break;
        }
    }

    // @save result image into file
    public function save($path, $type = 'png', $quality = 100) {
        $this->render();

        switch (strtolower($type)) {
            case 'gif'; imagegif($this->image, $path); break;
            case 'png'; imagepng($this->image, $path, ($quality > 9) ? 9 : $quality ); break;
            case 'jpg'; imagejpeg($this->image, $path, ($quality > 100) ? 100 : $quality ); break;
            case 'wbmp'; imagewbmp($this->image, $path, $this->pallete['background']); break;
        }
    }

}