<?php 

if ($argc == 1) {
  $prompt = "Please specify the php file to convert to c extension\n";
  $prompt .= "Usage: php ".basename(__FILE__)." [php file to convert to c extension]\n";
  die($prompt);
}

# try to install zephir first 
shell_exec("yes 2>/dev/null | ".__DIR__."/vendor/bin/zephir install > /dev/null 2>/dev/null");

require_once __DIR__.'/vendor/autoload.php';

$file = $argv[1];

$curDir = getcwd();

$file = $curDir."/".$file;

$zephirDir = $curDir."/build/zephir";

shell_exec("mkdir -p $zephirDir");

//cleap up temporary php files
shell_exec("rm -f $(find  $zephirDir -type f -name \"*.php\")");

$targetFile = $zephirDir."/".basename($file);

try {

  $fileFilter = new PHPtoCExt\FileFilter($file, $targetFile);
  $fileFilter->filter();

  $analyser = new PHPtoCExt\FileAnalyser($targetFile);

  $extension_names = [];
  foreach($analyser->getUserDefinedClasses() as $class) {
    $classCode = $analyser->getCodeInClass($class);
    $zephirNamespace = strtolower($analyser->getRootNamespaceOfClass($class));
    if (chdir($zephirDir)) {
      shell_exec("zephir init $zephirNamespace");
      $zephirSourceDir = "$zephirDir/$zephirNamespace/$zephirNamespace";
      if (is_readable($zephirSourceDir)) {
        $classFileName = $analyser->getClassNameWithoutNamespace($class).".php";
        $phpSourceFile = $zephirSourceDir."/".$classFileName; 
        file_put_contents($phpSourceFile, "<?php\n".$classCode);
        $extensionNames[] = $zephirNamespace;
      }
    }  
  }

  $extensionNames = array_unique($extensionNames);

  foreach($extensionNames as $extensionName) {
    $zephirProjectDir = $zephirDir."/".$extensionName;
    if (chdir($zephirProjectDir) ) {
      echo shell_exec(__DIR__."/vendor/bin/php-to-zephir phpToZephir:convertDir .");
      echo shell_exec(__DIR__."/vendor/bin/zephir build");
    }
  }

} catch (PHPtoCExt\PHPtoCExtException $e) {
  echo "Error: ".$e->getMessage()."\n";
}
