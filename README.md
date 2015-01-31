PHP-TO-C-Ext is a tool to allow developer to write codes in PHP and convert it directly to php extensions.

With PHP-TO-C-Ext tool, the developer can choose a php file that is not changed quite often and convert it to native php extension, thus speeding up the server response time and lower the resource consumption for each request.

PHP-TO-C-EXT is built on top of these great things:

+ Zephir(http://zephir-lang.com/)
+ PHP Parser(https://github.com/nikic/PHP-Parser)
+ PHP to Zephir(https://github.com/fezfez/php-to-zephir)

##Installation

1. Install composer
2. git clone git@github.com:jimthunderbird/php-to-c-extension.git
3. cd php-to-c-extension
4. composer.phar install

##Usage:

```sh
$ php [path/to/php-to-c-extension]/build_extensions.php [php file to convert to c extension]
```

or

```sh
$ php [path/to/php-to-c-extension]/build_extensions.php [directory containing php files to convert to c extension]
```

##Examples:

+ [A simple dummy extension](#example-01)
+ [One namespace and multiple classes in one file](#example-02)
+ [Organize multiple files in one directory](#example-03)
+ [Using for loop](#example-04)

###Example 01

Let's create a file named Dummy.php, it looks like this:

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

#### we can then execute:

```sh
php [path/to/php-to-c-extension]/build_extensions.php Dummy.php
```
    
After a while we should get dummy.so installed, then if we add the following line to php.ini 

```
extension=dummy.so
```
    
#### we will now have the class Dummy\Hello available for the user code.
#### If we write a file named test.php like the following:
```php
<?php
$o = new Dummy\Hello();
$o->say();
```
#### and if we run it with php -c [path/to/php.ini]/php.ini test.php, we should get "hello" printed.
#### You might have already noticed, the class Hello has the namespace Dummy and the extension name is dummy.so. 
#### In fact, in order to build a php extension with this tool, all classes must have a CamelCase namespace, and the extension name is the lowercase form of the namespace. 


###Example 02 
####Sometimes, for convenience, we might want to write a single file with one namespace and multiple classes, and we can do just that.
####Let's create a file named Dummy.php an it looks like the following:
```php 
<?php 
namespace Dummy; 
class Hello 
{
  public function say()
  {
    echo "hello\n";
  }
}

class Greeting 
{
  public function greet()
  {
    echo "greetings\n";
  }
}
``` 
####We can then execute 
####
    php [path/to/php-to-c-extension]/build_extensions.php Dummy.php 
#### Once we get the dummy.so built and added to the php.ini, we will have both Dummy\Hello and Dummy\Greeting classes available for the user code.

###Example 03 
####If we need to write more complicated php extensions, we usually need to maintain serveral source files, with PHP-TO-C-EXT tool, we can compile all files in a target directory.
####Let's create a directory src/Dummy, and inside we will 2 files, Hello.php and Greeting.php 
####Here is what src/Hello.php looks like:
```php 
<?php
namespace Dummy; 
class Hello 
{
  public function say()
  {
    echo "hello\n";
  }
}
```
####And here is what src/Greeting.php looks like:
```php 
<?php 
namespace Dummy;
class Greeting 
{
  public function greet()
  {
    echo "greetings\n";
  }
}
```
####Then if we execute 
####
    php [path/to/php-to-c-extension]/build_extensions.php src/Dummy 
####We will then have dummy.so built, and now both Dummy\Hello and Dummy\Greeting classes available for the user code.
####Notice that both Hello.php and Dummy.php must have namespace Dummy defined in the beginning.

###Example 04 
####The for loop is a common language structure in php, here we will be creating a Dummy\SumCalculator class and built that into the dummy.so extension.
####Let's create a file src/Dummy/SumCalculator.php, and it looks like this:
```php 
<?php 
namespace Dummy;

class SumCalculator 
{
  public function getSum($start, $end)
  {
    $sum = 0;
    for ($i = $start; $i <= $end; $i++) {
      $sum += $i;
    }
    return $sum;
  }
}
``` 
####Then if we execute 
####
    php [path/to/php-to-c-extension]/build_extensions.php src/Dummy 
####We will then have dummy.so built, and now both Dummy\SumCalculator will be available for the user code.
####We can do something like this in our user code:
```php 
<?php
$calculator = new Dummy\SumCalculator();
$calculator->getSum();
```
