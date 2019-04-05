# nginx-cache

## Usage
In your sites nginx conf file:

```conf
include /path/to/site/storage/nginx/cache.conf;

server {
	# ...
	
	include /path/to/site/storage/nginx/cache-server.conf;
	
	# ...
	
	location ~ \.php$ {
		# ...
		include /path/to/site/storage/nginx/cache-location.conf;
		# ...
	}
}
```
