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

###Example 1: a simple dummy extension
###Let's create a file named Dummy.php, it looks like this:
```php
<?php
namespace Dummy;
class Hello 
{
    public function say()
    {
        echo "hello";
    }
}
```
### we then can execute:
####
    php [path/to/php-to-c-extension]/build_extensions.php Dummy.php
### After a while we should get dummy.so installed, then if we add the following line to php.ini 
####
    extension=dummy.so
### we will now have the class Dummy\Hello available for the user code.
### If we write a file named test.php like the following:
```php
<?php
$o = new Dummy\Hello();
$o->say();
```
### and if we run it with php -c [path/to/php.ini]/php.ini test.php, we should get "hello" printed.
### You might have already notice, the class Hello has the namespace Dummy and the extension name is dummy.so. 
### In fact, in order to build a php extension with this tool, all classes must have a CamelCase namespace, and the extension name is the lowercase form of the namespace. 
