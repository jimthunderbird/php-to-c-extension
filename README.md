PHP-TO-C Extension is a tool to allow developer to write codes in PHP and convert it directly to php extensions.

With PHP-TO-C Extension tool, the developer can choose a php file that is not changed quite often and convert it to native php extension, thus speeding up the server request.

##Installation
####
    1. Install composer
    2. git clone git@github.com:jimthunderbird/php-to-c-extension.git
    3. cd php-to-c-extension
    4. composer.phar install

##Usage:
####
    php [path/to/php-to-c-extension/]build_extensions.php [php file to convert to c extension]
