Image Uploader
=============
Use to upload images to picasaweb, imgur, imageshack 

Support
-------
* Upload to your account 
* Use API for upload faster

Example:

Imgur & Imageshack same:
-------

	$ob = Ptc_Image_Uploader::factory('Imgur');
	$ob->setApi('your api here');
	$url = $ob->upload('real file path.jpg');	
	echo $url

Picasa:	
-------

	$ob = Ptc_Image_Uploader::factory('Picasa');
	$ob->login('your email', 'your password');
	$ob->setAlbumId(array('albumId 1', 'albumId 2'));
	$url = $ob->upload('real file path.jpg');	
	echo $url
	
Read plugins code in /Uploader to easy use.
	