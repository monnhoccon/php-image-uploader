Image Uploader
=============
Use to upload images to picasaweb, imgur, imageshack 

Support
-------
* Upload to your account 
* Use API for upload faster

Example
-----
	$ob = Ptc_Image_Uploader::factory('Imgur');
	$ob ->setApi('your api here');
	$url = $ob->upload('abc.jpg');
	
	echo $url
