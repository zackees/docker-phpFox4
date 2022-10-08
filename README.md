# Docker phpFox4

This is an dockerized version of phpFox4 representing a fresh install. This project is very alpha.
This project has been tested to work on Render.com. Please use at least 1GB of memory.

This app has been tested with a license key. It's unknown whether it will work with a trial key.

Pull requests for this app are strongly encouraged!
If you fork or like this app then please give us a star.

## Render.com

This should run phpFox4 out of the box.

## DigitalOcean

DigitalOcean will fail when it tries to issue a health check at port 8080.
To fix this, go into the `App Spec` panel and change `http_port: 8080` to `http_port: 80`


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
    
