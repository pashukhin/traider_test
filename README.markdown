# Test app for Healbe

## Install

You need to install composer first.

And than

`php composer.phar install`

nginx configuration

```
#/etc/nginx/common/upstream
upstream php-fpm
{
	server unix:/var/run/php5-fpm.sock;
}
```

```
#/etc/nginx/common/php-fpm
fastcgi_pass	php-fpm;
include fastcgi_params;
fastcgi_split_path_info			^(.+?\.php)(/.*)?$;
fastcgi_param	SCRIPT_FILENAME		$document_root$fastcgi_script_name;
fastcgi_param	PATH_TRANSLATED		$document_root$fastcgi_script_name;
set		$path_info		$fastcgi_path_info;
fastcgi_param	PATH_INFO		$path_info;
fastcgi_param	SERVER_ADMIN		email@example.com;
fastcgi_param	SERVER_SIGNATURE	nginx/$nginx_version;
fastcgi_index	index.php;
```

```
#/etc/nginx/sites-available/traider-test.dev
server
{
	listen	80;
	server_name	traider-test.dev www.traider-test.dev;
	root	/var/www/traider-test/web;
	index	index.php index.html index.htm;
	location "/"
	{
		index	index.html
		try_files	$uri $uri/	=404;
	}
	location ~ "^(.+?\.php)(/.*)?$"
	{
		try_files	$1	=404;
		include	common/php-fpm;
	}
}
```