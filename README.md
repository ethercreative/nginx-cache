![Nginx Cache](resources/banner.jpg)

# Nginx Cache
Harness the power of Nginx to statically cache your Craft site.

TTFB below 5ms!

## Usage
### Site Config
Update your sites Nginx conf file to include the three includes below (they are 
located in your sites storage directory):

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

### Reload Command
The reload command will be executed using `exec` after the config is saved. If 
you find that the command ins't working (nginx isn't reloading after save) it 
is likely due to PHP not having permission to run the command. You can give PHP 
permission by adding the command to your servers `sudoers` file. To edit your 
`sudoers` file run `$ sudo visudo` on your server, then add the following:

```text
www-data ALL=(ALL:ALL) NOPASSWD:nginx -s reload
```

Substitute `nginx -s reload` with what ever your command is. 

If you don't have a `sudoers` file you will need to manually reload Nginx after 
each save of the plugin config.

### Docker
If you are using docker ensure that your chosen cache directory and Craft's 
storage directory (or at lease the nginx directory in storage) are available to 
both your PHP and Nginx containers.
