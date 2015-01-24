<?php 

if ($argc == 1) {
  $prompt = "Please specify the php file to convert to c extension\n";
  $prompt .= "Usage: php ".basename(__FILE__)." [php file to convert to c extension]\n";
  die($prompt);
}

require_once "FileAnalyser.php";

$file = $argv[1];

$cur_dir = getcwd();

$zephir_dir = $cur_dir."/build/zephir";

shell_exec("mkdir -p $zephir_dir");

$analyser = new FileAnalyser($file);

$extension_names = [];
foreach($analyser->get_user_defined_classes() as $class) {
  $class_code = $analyser->get_code_in_class($class);
  $zephir_namespace = strtolower($analyser->get_root_namespace_of_class($class));
  if (chdir($zephir_dir)) {
    shell_exec("zephir init $zephir_namespace");
    $zephir_source_dir = "$zephir_dir/$zephir_namespace/$zephir_namespace";
    if (is_readable($zephir_source_dir)) {
      $class_file_name = $analyser->get_class_name_without_namespace($class).".php";
      $php_source_file = $zephir_source_dir."/".$class_file_name;
      file_put_contents($php_source_file, "<?php\n".$class_code);
      $extension_names[] = $zephir_namespace;
    }
  }  
}

$extension_names = array_unique($extension_names);

foreach($extension_names as $extension_name) {
  $zephir_project_dir = $zephir_dir."/".$extension_name;
  if (chdir($zephir_project_dir) ) {
    echo shell_exec(__DIR__."/vendor/bin/php-to-zephir phpToZephir:convertDir .");
    echo shell_exec(__DIR__."/vendor/bin/zephir build");
    //cleap up temporary directories
    shell_exec("rm -f $(find  $zephir_project_dir -type f -name \"*.php\")");
  }
}
