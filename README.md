# PHP-Image-Uploader
This project mainly help to upload images to some hosting services like Picasa, Imageshack, Imgur

* Author:     Phan Thanh Cong <ptcong90@gmail.com>
* Copyright:  2010-2014 Phan Thanh Cong.
* License:    http://www.opensource.org/licenses/mit-license.php  MIT License
* Version:    5.0 stateble

## Change Logs

##### Version 5.0.1: Apr 2, 2014
* Change class name from `\ChipVN\ImageUploader\ImageUploader` to `ChipVN_ImageUploader_ImageUploader`
* Support PHP >= 5 (does not require >= 5.3)

##### Version 5.0: Mar 07, 2014
* Supports composer
* Change factory method from `\ChipVN\Image_Uploader::factory` to `ChipVN_ImageUploader_ImageUploader::make`
* Make it simpler (only 5 php files)
* Remove ~~upload to local server~~
* Update Imageshack plugin


##### Version 4.0.1: Sep, 2013
* Fix Imgur auth

##### Version 4.0: Jul 25, 2013
* ~~Require PHP 5.3 or newer~~
* Rewrite all plugins to clear 

##### Version 1.0: June 17, 2010
* Upload image to Imageshack, Picasa

## Features
* ~~Upload image to local server~~
* Upload image to remote service like (picasa, imageshack, imgur)
* Remote: can free upload to imgur, imageshack or upload to your account. Picasa must be login to upload
* Easy to make new plugin for uploading to another service

## Usage
###### If you use composer
Add require `"ptcong/php-image-uploader": "5.0.*@dev"` to _composer.json_ and run `composer update` 

###### If you don't use composer
Download `ChipVN_Http_Request` from https://github.com/ptcong/php-http-class and put it to `ChipVN/Http` folder, then include two files:
    
    include 'ChipVN_Http_Request.php';
    include 'ChipVN_ImageUploader_ImageUploader.php';


then 
### Upload to Picasa.
To upload image to Picasa, you need to have some AlbumIds otherwise the image will be uploaded to _default_ album.
To create new AlbumId faster, you may use echo `$uploader->addAlbum('testing 1');`

    $uploader = ChipVN_ImageUploader_ImageUploader::make('Picasa');
    $uploader->login('your account here', 'your password here');
    // you can set upload to an albumId by array of albums or an album, system will get a random album to upload 
    //$uploader->setAlbumId(array('51652569125195125', '515124156195725'));
    //$uploader->setAlbumId('51652569125195125');
    echo $uploader->upload(getcwd(). '/test.jpg');
    // this plugin does not support transload image

### Upload to Imageshack

    $uploader = ChipVN_ImageUploader_ImageUploader::make('Imageshack');
    $uploader->login('your account here', 'your password here');
    $uploader->setApi('your api here');
    echo $uploader->upload(getcwd(). '/a.jpg');
    echo $uploader->transload('http://img33.imageshack.us/img33/6840/wz7u.jpg');

### Upload to Imgur

    $uploader = ChipVN_ImageUploader_ImageUploader::make('Imgur');
    // you may upload with anonymous account but may be the image will be deleted after a period of time
    // $uploader->login('your account here', 'your password here');
    echo $uploader->upload(getcwd(). '/a.jpg');
    echo $uploader->transload('http://img33.imageshack.us/img33/6840/wz7u.jpg');
