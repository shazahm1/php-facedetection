PHP Face Detection
==================

This class can detect one face in images ATM.

This is a pure PHP port of an existing JS code from Karthik Tharavaad.

Requirements
------------
PHP5 with GD

License
-------
GNU GPL v2 (See LICENSE.txt)



=========================
add by Charles

This package was original created by Karthik Tharavaad.
Two files has added in the original package, the first is FaceCapture class, this class extends capacity for FaceDetector class. The second is index2 file, the file scans image directory, then generate face recognition file.  

1 can adapter different type of image files, include bmp, jpg, gif, png.

2 can capture full face, the original capture a face profile, but it is not a full size face, so improve the ability to capture a full size face.

3 can get a defined size face createDefinedSizeFace, if handle many images, want same size face image.

4 the FaceDetector class is very excellent, but some image like very dark or some other reason, the programme can not get the face profile, if this condition, then throw an exception that no face detected.

5 add an index2.php, this file scans a directory, then find all the image file, after that can generate a face recognition image for each image file. For generate face image, must have the corresponding permission.
