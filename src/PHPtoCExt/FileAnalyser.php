<?php  

namespace PHPtoCExt;

class FileAnalyser
{
  private $file;
  private $file_content;
  private $user_defined_classes;

  public function __construct($file)
  {
    $this->file = $file; 
    $this->file_content = file_get_contents($file);
    $this->user_defined_classes = $this->get_user_defined_classes_in_file($file);
  }

  public function get_root_namespace_of_class($class)
  {
    return explode("\\", $class)[0];
  }

  public function get_first_class()
  {
    return $this->user_defined_classes[0];
  }

  public function get_code_in_class($class_name)
  {
    $class = new \ReflectionClass($class_name);
    $start_line = $class->getStartLine()-1; // get_start_line() seems to start after the {, we want to include the signature
    $end_line = $class->getEndLine();
    $num_lines = $end_line - $start_line;
    $namespace = $this->get_root_namespace_of_class($class_name);
    $class_code = "namespace $namespace;\n\n".implode("\n",array_slice(explode("\n",$this->file_content),$start_line,$num_lines))."\n";
    return $class_code;
  }

  public function get_class_name_without_namespace($class_name)
  {
    return array_pop(explode("\\", $class_name));
  }

  public function get_user_defined_classes()
  {
    return $this->user_defined_classes;
  }

  private function get_user_defined_classes_in_file() 
  {
    $previous_defined_classes = get_declared_classes();
    require_once $this->file;
    $current_defined_classes = get_declared_classes();
    $user_defined_classes = array_diff($current_defined_classes, $previous_defined_classes);
    $result = [];
    foreach($user_defined_classes as $class) {
      $result[] = $class;
    }
    return $result;
  }
}
