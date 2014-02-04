# PHP-Image-Uploader
This project mainly help to upload images to some services like Picasa, Imageshack, Imgur or something (just a plugin)
* Author: Phan Thanh Cong 
* Contact: ptcong90@gmail.com
* Copyright: (c) 2010 chiplove.9xpro
* Version: 4.0
* License: MIT

***
## Change Logs

#### Version 4.0.1: Sep, 2013
* Fix Imgur auth

#### Version 4.0: Jul 25, 2013
* Use namespace
* Require PHP 5.3 or later
* Rewrite all plugins to clear 

#### Version 1.0: June 17, 2010
* Upload image to Imageshack, Picasa

***
## Features
* Upload image to local server
* Upload image to remote service like (picasa, imageshack, imgur)
* Remote: can free upload to imgur, imageshack or upload to your account. Picasa must be login to upload
* Easy to make new plugin for uploading to another service

## Usage
The first

    include 'ChipVN/Loader.php';
    \ChipVN\Loader::registerAutoLoad();


then 
### Upload to Picasa.
To upload image to Picasa, you need to have some AlbumIds otherwise the image will be uploaded to _default_ album.
To create new AlbumId faster, you may use echo `$uploader->addAlbum('testing 1');`

    $uploader = \ChipVN\Image_Uploader::factory('Picasa');
    $uploader->login('your account here', 'your password here');
    // you can set upload to an albumId by array of albums or an album, system will get a random album to upload 
    //$uploader->setAlbumId(array('51652569125195125', '515124156195725'));
    //$uploader->setAlbumId('51652569125195125');
    echo $uploader->upload(getcwd(). '/test.jpg');
    // this plugin does not support transload image

### Upload to Imageshack
    $uploader = \ChipVN\Image_Uploader::factory('Imageshack');
    // you may upload with anonymous account but may be the image will be deleted after a period of time
    // $uploader->login('your account here', 'your password here');
    echo $uploader->upload(getcwd(). '/a.jpg');
    echo $uploader->transload('http://img33.imageshack.us/img33/6840/wz7u.jpg');

### Upload to Imgur
    $uploader = \ChipVN\Image_Uploader::factory('Imgur');
    // you may upload with anonymous account but may be the image will be deleted after a period of time
    // $uploader->login('your account here', 'your password here');
    echo $uploader->upload(getcwd(). '/a.jpg');
    echo $uploader->transload('http://img33.imageshack.us/img33/6840/wz7u.jpg');


### Upload to Local server
    // This plugin to upload file that was submited from client
    $uploader = \ChipVN\Image_Uploader::factory();
    echo $uploader->upload($_FILES['uploadfile'], $destination);

***
The end