<?php 
if ($argc == 1) {
  $prompt = "Please specify the php file to convert to c extension\n";
  $prompt .= "Usage: php ".basename(__FILE__)." [php file to convert to c extension]\n";
  die($prompt);
}

$zephirCommand = __DIR__."/vendor/bin/zephir";

$curDir = getcwd();

$jsoncDirContent = trim(shell_exec("find ".__DIR__."/vendor/phalcon/zephir/json-c -type f"));

if (strlen($jsoncDirContent) == 0) { //that means zephir is not installed 
  if (chdir(__DIR__."/vendor/phalcon/zephir")) {
    print "Installing zephir...\n";
    system("./install-json");
    system("./install"); 
  } else {
    die("Error changing to zephir vendor directory!\n");
  }

  if (!chdir($curDir)) {
    die("Error changing to current directory!\n");
  }
}

require_once __DIR__.'/vendor/autoload.php';

$input = $argv[1];

$curDir = getcwd();

$buildDir = $curDir."/build";

//remove the old build dir, if there is one 
system("rm -rf $buildDir/");

$zephirDir = $buildDir."/zephir";

system("mkdir -p $zephirDir");

$extensionNames = [];

$file = "";
$fileContent = "";

$inputDir = "";

if (is_file($input)) {
  $file = $curDir."/".$input;
  $inputDir = dirname($file);
  $fileContent = file_get_contents($file);
} else if (is_dir($input)) {
  $inputDir = $input;
  $fileContent = "<?php\n";

  $files = explode("\n",trim(shell_exec("find $input -type f -name \"*.php\"")));

  foreach($files as $f) {
    $fc = str_replace("<?php","",file_get_contents($f));
    $fc = trim($fc);
    $fileContent .= $fc."\n\n";
  }

  $file = $zephirDir."/".array_pop(explode("/",$input)).".php";
}

file_put_contents($file, $fileContent);

$targetFile = $zephirDir."/".basename($file);

$fileFilter = null;

try {

  $fileFilter = new PHPtoCExt\FileFilter($file, $targetFile, $inputDir);
  $fileFilter->filter();
   
  $analyser = new PHPtoCExt\FileAnalyser($targetFile);

  foreach($analyser->getUserDefinedClasses() as $class) {
    $classCode = $analyser->getCodeInClass($class);
    $zephirNamespace = strtolower($analyser->getRootNamespaceOfClass($class));
    if (chdir($zephirDir)) {
      system("$zephirCommand init $zephirNamespace");
      $classFileDir = strtolower(str_replace("\\","/",$analyser->getNamespaceOfClass($class)));
      system("mkdir -p ".$zephirNamespace."/".$classFileDir);
      if (!is_readable($zephirNamespace."/".$classFileDir)) {
        throw new PHPtoCExt\PHPtoCExtException("Fail to create directory ".$classFileDir);
      }
      $classFileName = $zephirNamespace."/".$classFileDir."/".$analyser->getClassNameWithoutNamespace($class).".php";
      file_put_contents($classFileName, "<?php\n".$classCode);
      $extensionNames[] = $zephirNamespace;
    }  
  }

  $extensionNames = array_unique($extensionNames);

} catch (PHPtoCExt\PHPtoCExtException $e) {
  die("Error: ".$e->getMessage()."\n");
}

foreach($extensionNames as $extensionName) {
  $zephirProjectDir = $zephirDir."/".$extensionName;
  if (chdir($zephirProjectDir) ) {
    system(__DIR__."/vendor/bin/php-to-zephir phpToZephir:convertDir .");

    //now do post convertion searches and replaces
    echo "Performing post conversion processing...\n";

    $convertedFiles = explode("\n",trim(shell_exec("find . -type f -name \"*.zep\"")));

    foreach($convertedFiles as $file) {
      $fileFilter->postFilter($file);
    }

    echo "Finished post conversion processing\n";

    echo "Building extension...\n";

    system("$zephirCommand install");

  }
}
