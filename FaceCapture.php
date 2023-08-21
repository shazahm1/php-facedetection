<?php
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

namespace svay;

use Exception;
use svay\Exception\NoFaceException;

class FaceCapture extends FaceDetector
{
  protected $extension;
  protected $fullSizeFaceCanvas = null;

  public function faceDetect($file)
  {

      if (is_resource($file)) {

          $this->canvas = $file;

      } elseif (is_file($file)) {
          $pathInfo =  pathinfo($file);
          $this->extension = $pathInfo['extension'];
          // echo "extension is ".$this->extension;
          switch($this->extension) {
            case 'bmp':
              $this->canvas = imagecreatefrombmp($file);
              break;
            case 'png':
              $this->canvas = imagecreatefrompng($file);
              break;
            case 'jpg':
              $this->canvas = imagecreatefromjpeg($file);
              break;
            case 'gif':
              $this->canvas = imagecreatefromgif($file);
              break;
            default:
              $this->canvas = imagecreatefromjpeg($file);
          }


      } elseif (is_string($file)) {

          $this->canvas = imagecreatefromstring($file);

      } else {

          throw new Exception("Can not load $file");
      }

      $im_width = imagesx($this->canvas);
      $im_height = imagesy($this->canvas);

      //Resample before detection?
      $diff_width = 320 - $im_width;
      $diff_height = 240 - $im_height;
      if ($diff_width > $diff_height) {
          $ratio = $im_width / 320;
      } else {
          $ratio = $im_height / 240;
      }

      if ($ratio != 0) {
          $this->reduced_canvas = imagecreatetruecolor($im_width / $ratio, $im_height / $ratio);

          imagecopyresampled(
              $this->reduced_canvas,
              $this->canvas,
              0,
              0,
              0,
              0,
              $im_width / $ratio,
              $im_height / $ratio,
              $im_width,
              $im_height
          );

          $stats = $this->getImgStats($this->reduced_canvas);

          $this->face = $this->doDetectGreedyBigToSmall(
              $stats['ii'],
              $stats['ii2'],
              $stats['width'],
              $stats['height']
          );

          if ($this->face['w'] > 0) {
              $this->face['x'] *= $ratio;
              $this->face['y'] *= $ratio;
              $this->face['w'] *= $ratio;
          }

      } else {
          $stats = $this->getImgStats($this->canvas);

          $this->face = $this->doDetectGreedyBigToSmall(
              $stats['ii'],
              $stats['ii2'],
              $stats['width'],
              $stats['height']
          );
      }
      // var_dump($this->face);
      return ($this->face['w'] > 0);

  }

  public function drawFace()
  {
      $color = imagecolorallocate($this->canvas, 255, 0, 0); //red

      imagerectangle(
          $this->canvas,
          $this->face['x'],
          $this->face['y'],
          $this->face['x']+$this->face['w'],
          $this->face['y']+ $this->face['w'],
          $color
      );

      $this->createImageFile($this->extension, null, $this->canvas);
  }

  /**
   * Crops the face from the photo.
   * Should be called after `faceDetect` function call
   * If file is provided, the face will be stored in file, other way it will be output to standard output.
   *
   * @param string|null $outFileName file name to store. If null, will be printed to output
   *
   * @throws NoFaceException
   */
  public function cropFace($outFileName = null)
  {
      if (empty($this->face)) {
          throw new NoFaceException('No face detected');
      }

      $canvas = imagecreatetruecolor($this->face['w'], $this->face['w']);
      imagecopy($canvas, $this->canvas, 0, 0, $this->face['x'], $this->face['y'], $this->face['w'], $this->face['w']);

      $this->createImageFile($this->extension, $outFileName, $canvas);
  }

  /**
    * to get a full face, we will get a bigger image size, this size includes face outline.
    * $scaleWidth is how many times size of the face outline.
    */
  public function cropFullFace($outFileName = null, $scaleWidth = 1.9)
  {
      if (empty($this->face)) {
          throw new NoFaceException('No face detected');
      }

      if (!(is_numeric($scaleWidth) && $scaleWidth >= 1)) {
        throw new NoFaceException('Scale width must equal or bigger then 1');
      }

      $canvas = imagecreatetruecolor($this->face['w'] * $scaleWidth , $this->face['w'] * $scaleWidth);
      $startY = ($this->face['y'] - ($scaleWidth - 1) *  $this->face['w'] /2 >0) ? round($this->face['y'] - ($scaleWidth - 1) * $this->face['w']/2, 0) : 0;
      $startX = ($this->face['x'] - ($scaleWidth - 1) *  $this->face['w'] /2 >0) ? round($this->face['x'] - ($scaleWidth - 1) * $this->face['w'] /2, 0) : 0;
      imagecopy($canvas, $this->canvas, 0, 0, $startX, $startY, $this->face['w'] * $scaleWidth, $this->face['w'] * $scaleWidth );

      $this->fullSizeFaceCanvas = $canvas;
      $this->createImageFile($this->extension, $outFileName, $canvas);

  }

  protected function createImageFile($fileExtension, $outFileName, $canvas)
  {
      switch($fileExtension) {
            case 'bmp':
                if ($outFileName === null) {
                  header('Content-type: image/bmp');
                }
                imagebmp($canvas, $outFileName);
                break;
            case 'jpg':
              if($outFileName === null) {
                header('Content-type: image/jpeg');
              }
              imagejpeg($canvas, $outFileName);
              break;
            case 'gif':
              if($outFileName === null) {
                header('Content-type: image/gif');
              }
              imagegif($canvas, $outFileName);
              break;
            case 'png':
                if($outFileName === null) {
                  header('Content-type: image/png');
                }
                imagepng($canvas, $outFileName);
                break;
            default:
              if($outFileName === null) {
                header('Content-type: image/jpeg');
              }
              imagejpeg($canvas, $outFileName);
          }

  }

  /** from full face to generate the defined width and height size image file, face height should bigger then face width
   */
  public function createDefinedSizeFace($file = null, $faceWidth = 300, $faceHeight = 400) {
    if (empty($this->fullSizeFaceCanvas)) {
        throw new NoFaceException('No face canvas has generated, run cropFullFace function first');
    }

    if ($faceWidth > $faceHeight) {
      $temp = $faceHeight;
      $faceHeight = $faceWidth;
      $faceWidth = $temp;
    }

    $width = imagesx($this->fullSizeFaceCanvas);
    $height  = imagesy($this->fullSizeFaceCanvas);
    //first make a compressed square image.
    $canvas = imagecreatetruecolor($faceHeight, $faceHeight);
    imagecopyresampled($canvas, $this->fullSizeFaceCanvas, 0, 0, 0, 0, $faceHeight, $faceHeight, $width, $height);
    //second make a defined size face image.
    $newImage = imagecreatetruecolor($faceWidth, $faceHeight);
    //from the square image to defined size image, width 300px, height 400px.
    imagecopy($newImage, $canvas, 0, 0, 0, 0, $faceWidth, $faceHeight);
    $this->createImageFile($this->extension, $file, $newImage);
    imagedestroy($canvas);
    imagedestroy($newImage);
  }

  public function getFaceArray() {
    return $this->face;
  }

}
