<?php

class Image {

  /**
   * The image container variable
   * Should be false if no image present
   * Otherwise should hold the GD Image Object
   * @var &gd.return.identifier
   */
  private $_img;

  /**
   * The type of the image loaded
   * Should be false or any of the jpg|jpeg|png|gif
   * @var string
   */
  private $_type;

  /**
   * The constructor function
   *
   * Set the default false value to both the variable
   */
  public function __construct() {
    $this->_img = FALSE;
    $this->_type = FALSE;
  }

  /**
   * The loader function
   * Loads the image from a file to the $this->img variable
   *
   * Also checks if the file exists and of valid image type by checking the extension
   * @param string $filename The path of the file to be loaded
   * @return boolean true on success, false otherwise
   */
  public function load($filename) {
    //first check if the file exists
    if (!file_exists($filename) && is_dir($filename)) {
      return FALSE;
    }

    // get mime type of the image
    $fInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fInfo, $filename);
    finfo_close($fInfo);

    list(, $type) = explode('/', $mimeType);
    $this->_type = $type;

    switch ($type) {
      case 'jpeg' :
        $this->_img = imagecreatefromjpeg($filename);
        break;

      case 'png' :
        $this->_img = imagecreatefrompng($filename);
        break;

      case 'gif' :
        $this->_img = imagecreatefromgif($filename);
        break;

      default :
        return FALSE;
    }

    if (imagealphablending($this->_img, TRUE) && imagesavealpha($this->_img, TRUE) ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Saves the image in a file
   *
   * The image from img variable is saved
   *
   * @param string $filename The path where the file is to be saved
   * @param string $type The type of the saved file. Use inherit for original, or use jpg|jpeg|png|gif
   * @param int $jpeg_quality The quality of the jpeg image (used only if being saved as jpeg), 0 - 100 (max quality)
   * @param int $png_compression The compression of the PNG image (used only if being saved as png), 0 (max quality, min compression) - 9
   * @return boolean true on success, false otherwise
   */
  public function save($filename, $type = 'inherit', $jpeg_quality = 100, $png_compression = 0) {
    $ext = 'inherit' == $type ? $this->_type : $type;

    switch ($ext) {
      case 'jpg' :
      case 'jpeg' :
        imagejpeg($this->_img, $filename, $jpeg_quality);
        break;

      case 'png' :
        imagepng($this->_img, $filename, $png_compression);
        break;

      case 'gif' :
        imagegif($this->_img, $filename);
        break;

      default :
        return FALSE;
    }

    return TRUE;
  }

  public function resize($width, $height) {
    $new_image = imagecreatetruecolor($width, $height);
    imagecopyresampled($new_image, $this->_img, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
    $this->_img = $new_image;
  }

  public function resizeToHeight($height) {
    $ratio = $height / $this->getHeight();
    $width = $this->getWidth() * $ratio;
    $this->resize($width, $height);
  }

  public function resizeToWidth($width) {
    $ratio = $width / $this->getWidth();
    $height = $this->getHeight() * $ratio;
    $this->resize($width, $height);
  }

  public function getWidth() {
    return imagesx($this->_img);
  }

  public function getHeight() {
    return imagesy($this->_img);
  }

  /**
   * Output the image directly into the browser.
   * Also stops execution of other scripts by calling die() function
   *
   * @uses die
   * @param string $type The type of the saved file. Use inherit for original, or use jpg|jpeg|png|gif
   * @param int $jpeg_quality The quality of the jpeg image (used only if being saved as jpeg), 0 - 100 (max quality)
   * @param int $png_compression The compression of the PNG image (used only if being saved as png), 0 (max quality, min compression) - 9
   * @return boolean false on failure
   */
  public function output($type = 'inherit', $jpeg_quality = 100, $png_compression = 0) {
    if ($this->_img == FALSE) {
      return FALSE;
    }

    header('Content-type: image/' . $this->_type);
    $this->save(NULL, $type, $jpeg_quality, $png_compression);
    //die to stop execution
    die();
  }

  /**
   * Flip the image horizontally, vertically or both
   *
   * Manipulates the image object saved in the img variable
   *
   * @param string $type Flipping type, can be h|horizontal|v|vertical|both
   * @return boolean true on success, false on failure
   */
  public function flip($type) {
    //first get the height and width
    $width = $this->getWidth();
    $height = $this->getHeight();

    //create the empty destination image
    $dest = imagecreatetruecolor($width, $height);
    imagealphablending($dest, FALSE);
    imagesavealpha($dest, TRUE);

    //now work with the type and do the necessary flipping
    switch ($type) {
      case 'v' : //vertical flip
      case 'vertical' :
        for ($i = 0; $i < $height; $i++) {
          /**
           * What we do here is pixel wise row flipping
           * The first row of pixels of the source image (ie, when $i = 0)
           * goes to the last row of pixels of the destination image
           *
           * So, mathematically, for the row $i of the source image
           * the corresponding row of the destination should be
           * $height - $i - 1
           * -1, because y and x both co-ordinates are calculated from zero
           */
          imagecopy($dest, $this->_img, 0, ($height - $i - 1), 0, $i, $width, 1);
        }
        break;

      case 'h' : //horizontal flip
      case 'horizontal' :
        for ($i = 0; $i < $width; $i++) {
          /**
           * Here we apply the same logic for other direction
           * The first column of pixels of the source image
           * goes to the last column of pixels of the destination image
           *
           * So, for the $i -th column of the source
           * the column of the destination would be
           * $width - $i - 1
           */
          imagecopy($dest, $this->_img, ($width - $i - 1), 0, $i, 0, 1, $height);
        }
        break;

      case 'both' :
        //we simply return using recursive call
        if ($this->flip('horizontal') && $this->flip('vertical')) {
          return TRUE;
        }
        else {
          return FALSE;
        }
        break;
      default :
        return FALSE;
    }

    //now make the changes
    imagedestroy($this->_img);
    $this->_img = $dest;

    return TRUE;
  }

  /**
   * Rotate the image to the given angle, filled with given color and alpha
   *
   * Rotates in clockwise direction and takes hexadecimal color code as input
   *
   * @param int $angle The rotational angle
   * @param string $bgColor The background color code in hex. Optional, default is ffffff (white)
   * @param int $alpha The alpha value, 0 for opaque, 127 for transparent, anything between for translucent
   * @return void
   */
  public function rotate($angle, $bgColor = 'ffffff', $alpha = 0) {
    $angle = abs($angle);
    //make the value for clockwise rotation
    $rAngle = 360 - ($angle % 360);

    $red = 255;
    $green = 255;
    $blue = 255;

    extract($this->hexToRgb($bgColor), EXTR_OVERWRITE);

    $color = imagecolorallocatealpha($this->_img, $red, $green, $blue, $alpha);

    $dest = imagerotate($this->_img, $rAngle, $color);

    if (FALSE !== $dest) {
      imagealphablending($dest, TRUE);
      imagesavealpha($dest, TRUE);
      imagedestroy($this->_img);
      $this->_img = $dest;
    }
  }

  public function greyScale() {
    imagefilter($this->_img, IMG_FILTER_GRAYSCALE);
  }

  public function reverseColor() {
    imagefilter($this->_img, IMG_FILTER_NEGATE);
  }

  /**
   * Change image brightness.
   *
   * @param $level
   *  Brightness level from -255 to 255
   */
  public function setBrightness($level) {
    imagefilter($this->_img, IMG_FILTER_BRIGHTNESS, $level);
  }

  /**
   * Change image contrast.
   *
   * @param $level
   *  Contrast level from -255 to 255
   */
  public function setContrast($level) {
    imagefilter($this->_img, IMG_FILTER_CONTRAST, $level);
  }

  public function setBlur() {
    //imagelayereffect($this->_img, IMG_EFFECT_OVERLAY);
    imagefilter($this->_img, IMG_FILTER_GAUSSIAN_BLUR);
  }

  /**
   * Converts hexadecimal to RGB color array
   * @link http://www.anyexample.com/programming/php/php_convert_rgb_from_to_html_hex_color.xml
   * @uses hexdec http://php.net/manual/en/function.hexdec.php
   * @access private
   * @param string $color 6 or 3 character long hexadecimal code
   * @return array with red, green, blue keys and corresponding values
   */
  private function hexToRgb($color) {
    if ($color[0] == '#') {
      $color = substr($color, 1);
    }

    if (strlen($color) == 6) {
      list($r, $g, $b) = array(
        $color[0] . $color[1],
        $color[2] . $color[3],
        $color[4] . $color[5]
      );
    }
    elseif (strlen($color) == 3) {
      list($r, $g, $b) = array(
        $color[0] . $color[0],
        $color[1] . $color[1],
        $color[2] . $color[2]
      );
    }
    else {
      return array('red' => 255, 'green' => 255, 'blue' => 255);
    }

    $r = hexdec($r);
    $g = hexdec($g);
    $b = hexdec($b);

    return array('red' => $r, 'green' => $g, 'blue' => $b);
  }
}