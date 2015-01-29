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
    php [path/to/php-to-c-extension]/build_extensions.php [php file to convert to c extension]

##Example 1:
###A simple hello world extension
###Let's create a file named HelloWorld.php, it looks like this:
```php
<?php
namespace HelloWorld;
class HelloWorld 
{
    public function hello()
    {
        echo "hello world";
    }
}
```
### we then can execute:
####
    php [path/to/php-to-c-extension]/build_extensions.php HelloWorld.php
### After a while we should get helloworld.so installed, then if we add the following line to php.ini 
####
    extension=helloworld.so
### we will now have the class HelloWorld\HelloWorld available for the user code.
### If we write a file named test.php like the following:
```php
<?php
$helloWorld = new HelloWorld\HelloWorld();
$helloWorld->hello();
```
### and if we run it with php -c [path/to/php.ini]/php.ini test.php, we should get "hello world" printed.
