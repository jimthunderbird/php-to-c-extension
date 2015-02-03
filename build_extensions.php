<?php 
if ($argc == 1) {
  $prompt = "Please specify the php file to convert to c extension\n";
  $prompt .= "Usage: php ".basename(__FILE__)." [php file to convert to c extension]\n";
  die($prompt);
}

# try to install zephir first 
shell_exec("yes 2>/dev/null | ".__DIR__."/vendor/bin/zephir install > /dev/null 2>/dev/null");

require_once __DIR__.'/vendor/autoload.php';

$input = $argv[1];

$curDir = getcwd();

$buildDir = $curDir."/build";

//remove the old build dir, if there is one 
shell_exec("rm -rf $buildDir/");

$zephirDir = $buildDir."/zephir";

shell_exec("mkdir -p $zephirDir");

$extensionNames = [];

$file = "";
$fileContent = "";

if (is_file($input)) {
  $file = $curDir."/".$input;
  $fileContent = file_get_contents($file);
} else if (is_dir($input)) {
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

$buildExtension = !isset($buildExtension)? function($file, $targetFile) use (&$extensionNames, &$zephirDir) {

  try {

    $fileFilter = new PHPtoCExt\FileFilter($file, $targetFile);
    $fileFilter->filter();

    $analyser = new PHPtoCExt\FileAnalyser($targetFile);

    foreach($analyser->getUserDefinedClasses() as $class) {
      $classCode = $analyser->getCodeInClass($class);
      $zephirNamespace = strtolower($analyser->getRootNamespaceOfClass($class));
      if (chdir($zephirDir)) {
        shell_exec("zephir init $zephirNamespace");
        $classFileDir = strtolower(str_replace("\\","/",$analyser->getNamespaceOfClass($class)));
        shell_exec("mkdir -p ".$zephirNamespace."/".$classFileDir);
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
    echo "Error: ".$e->getMessage()."\n";
  }

}:$buildExtension;

$buildExtension($file, $targetFile);

foreach($extensionNames as $extensionName) {
  $zephirProjectDir = $zephirDir."/".$extensionName;
  if (chdir($zephirProjectDir) ) {
    echo shell_exec(__DIR__."/vendor/bin/php-to-zephir phpToZephir:convertDir .");
    echo shell_exec(__DIR__."/vendor/bin/zephir build");
  }
}
