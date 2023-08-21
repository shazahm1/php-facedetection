<?php
  /**
   * test a directory, when scan the dir, first get all the image files.
   * second generate a face crop image for every image file.
   */
  use svay\FaceDetector;
  use svay\FaceCapture;

  require_once('FaceDetector.php');
  require_once('FaceCapture.php');
  require_once('Exception/NoFaceException.php');


  set_time_limit(0);
  //loop dir to get all the files.
  function getAllFiles($dir)
  {
    if (!is_dir($dir)) {
      return false;
    }
    $files = [];
    readFiles($dir, $files);
    // var_dump($files);
    return $files;
  }


  function readFiles($dir, &$files, $imageFileType = ['bmp','jpg','jpeg','gif','png'])
  {
    $handle = opendir($dir);
    while (($file = readdir($handle)) !== false) {
      if ($file == '.' || $file == '..')
        continue;

      $filePath = $dir == '.'? $file : $dir.DIRECTORY_SEPARATOR.$file;
      if (is_link($filePath)) {
        contiune;
      } else if (is_dir($filePath)) {
        readFiles($filePath, $files);
      } else {
        $fileType = pathinfo($dir.'/'.$file)['extension'];
        if (!in_array($fileType, $imageFileType)) {
          continue;
        }
        if (strpos($file, '_cropface')===false) {
          $files[] = $dir.DIRECTORY_SEPARATOR.$file;
        }
      }
    }
    closedir($handle);
  }


  $files = [];
  //test directory
  $dir = 'OperaHouse';
  $listFiles = getAllFiles($dir);

  foreach($listFiles as $key => $file)
  {
    try{
        $detector = new FaceCapture('detection.dat');
        $detector->faceDetect($file);
        $pathInfo = pathinfo($file);
        $tempFile  = $pathInfo['dirname'].DIRECTORY_SEPARATOR.$pathInfo['filename'].'_fullface.'.$pathInfo['extension'];
        $detector->cropFullFace($tempFile , 1.9);
        $faceWidth = 300;
        $faceHeight = 400;
        $newFile = $pathInfo['dirname'] .DIRECTORY_SEPARATOR. $pathInfo['filename'] . '_cropface.'.$pathInfo['extension'];
        $detector->createDefinedSizeFace($newFile, $faceWidth, $faceHeight);
        echo "generate crop face file ". htmlentities($newFile)." successfully<br>";
        //delete temp file.
        unlink($tempFile);
      } catch(Exception $e) {
          echo htmlentities($file) ." has exception, the detail is ". htmlentities($e->getMessage())."<br>";
      }
  }
 ?>
