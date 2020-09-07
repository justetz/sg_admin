# SG_ADMIN

Admin page for RPI Student Government. Can be used for viewing events, student profiles, and history, along with management of all these features.
Written in [PHP](https://www.php.net/), and uses [sg_data](https://github.com/wtg/sg_data) as its API.

## Installation (Windows/WSL)

NOTE: Before beginning setup here, you need [sg_data](https://github.com/wtg/sg_data) to already be setup.

1. Make a clone of the sg_admin repository on your computer
2. Download PHP through [XAMPP for Windows](https://www.apachefriends.org/index.html)
3. Download [Composer](https://getcomposer.org/), a PHP dependency manager
4. Navigate to the location of your sg_admin repository in a Windows Command Prompt
    - Verify that [Composer](https://getcomposer.org/) is installed by typing `composer -v`
5. Once inside the project directory, install the proper packages for Composer by running `composer install`
6. Open up Apache's httpd.conf file through [XAMPP Control Panel](https://www.apachefriends.org/index.html)
    - Config button -> Apache (httpd.conf)
7. Navigate to the bottom of the conf file and add a new VirtualHost entry by copying and pasting this code:
    ```
    <VirtualHost localhost:80>
        DocumentRoot "[location of local repository]\sg_admin"
        ServerName sg_admin.wtg
        ServerAlias sg_admin.wtg
        DirectoryIndex index.php
        <Directory "[location of local repository]\sg_admin">
            Require all granted
        </Directory>
        SetEnv API_URL http://localhost:3000/
    </VirtualHost>
    ```
8. Navigate to your System32 directory (be extremely careful not to unintentionally modify anything) and open your hosts file.
    - Add a new line to the bottom of the hosts file:
    ```
    127.0.0.1 sg_admin.wtg
    ```
9. Start the Apache server through the [XAMPP Control Panel](https://www.apachefriends.org/index.html) by clicking the Start button next to Apache.
10. Navigate to `http://sg_admin.wtg/` in your browser and verify that the Admin site is up.
11. If you have not already setup [sg_public](https://github.com/justetz/sg_public), make sure follow that process as well.
    
## Installation (macOS)

NOTE: Before beginning setup here, you need [sg_data](https://github.com/wtg/sg_data) to already be setup.

1. Make a clone of the sg_admin repository on your computer
2. PHP may already be installed on your computer, try running `php -v` to confirm this. If not, [install PHP](https://www.php.net/manual/en/install.macosx.php).
3. Download [Composer](https://getcomposer.org/), a PHP dependency manager
4. Navigate to the location of your sg_admin repository in Terminal
    - Verify that [Composer](https://getcomposer.org/) is installed by typing `composer -v`
5. Once inside the project directory, install the proper packages for Composer by running `composer install`
6. Run `php -S localhost:####` where `####` is a port (i.e. 3000 or 8080) to run the app on. (NOTE: this port needs to be different than the port used for sg_data or sg_public).
7. Navigate to `localhost:####` in your browser and verify that the Admin site is up.
8. If you have not already setup [sg_public](https://github.com/justetz/sg_public), make sure follow that process as well.
