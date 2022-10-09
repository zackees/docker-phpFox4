# Docker phpFox4

This is an dockerized version of phpFox4 representing a fresh install. This project is very alpha.
This project has been tested to work on

  * [x] Render.com
    * live demo: [https://docker-phpfox4.onrender.com/index.php/](https://docker-phpfox4.onrender.com/index.php/)
  * [ ] Digital Ocean (stops progression during setup)
  * [x] railway.app
    * live demo [https://docker-phpfox4-production.up.railway.app/](https://docker-phpfox4-production.up.railway.app/)
  * [x] fly.io
    * live demo: [https://phpfox4.fly.dev/](https://phpfox4.fly.dev/)
  
Please use at least 2GB of memory.

The common theme for all the docker app hosts is that they want to do a health check on port 8080,
and this needs to be overriden so that the health check is routed to port 80.

This app has been tested with a license key. It's unknown whether it will work with a trial key.

Pull requests for this app are strongly encouraged!
If you fork or like this app then please give us a star.

## Render.com

This should run phpFox4 out of the box.

## DigitalOcean

DigitalOcean will fail when it tries to issue a health check at port 8080.
To fix this, go into the `App Spec` panel and change `http_port: 8080` to `http_port: 80`

## Railway.app

When the app is deployed you must override the PORT environmental variable and set it to port 80.

## Fly.io

Setup the account with 2GB of memory or more than execute
`fly launch`
In the generated fly.toml file change `internal_port=8080` -> `internal_port=80`


# Test run

  * `docker-compose down --rmi=all && docker-compose up`
    * This will delete any previous image, build and then run a new instance.
  * Settings
    * Licence
      * ID: YOUR PHPFox LICENSE ID
      * Key: YOUR PHPFox LICENCE KEY
    * Database
      * Host: site url or localhost
      * Name: mysql
      * user name: admin
      * pass: See log output
        * Example: `You can now connect to this MySQL Server with c4ZobXEfHpI0`, where `c4ZobXEfHpI0` is the password in this case.
      * port: 3306 
    * Sitename: localhost
    * Add in the user name and password. This will work for logging in too.
    * Go ahead and select all the components to install.
    * Once the app has installed itself then use the admin email/password to log in.
  
## Viewing the state of the database

  * Install MySQL Workbench
  * Menu: `Database` -> `Connect to Database`
  * Connection Settings
    * hostname: 127.0.0.1
    * username: admin
    * password: (get password in log file, see above)
    
# Notes:
In `PF.Base/file/settings/server.sett.php` example:
```php
<?php
$_CONF['db']['driver'] = 'mysqli';
$_CONF['db']['host'] = 'master_server_ip'; // host
$_CONF['db']['user'] = 'username';
$_CONF['db']['pass'] = 'pass';
$_CONF['db']['name'] = 'name';
$_CONF['db']['prefix'] = 'phpfox_';
$_CONF['db']['port'] = '3306';
 
$_CONF['db']['slave'] = true;
$_CONF['db']['slave_servers'] = [
    [
     'host'=>'slave_ip',
     'user'=>'slave_user',
     'pass'=>'slave_pass',
     'port'=>'slave_port'
    ]
];
```
