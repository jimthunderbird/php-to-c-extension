<?php  

namespace PHPtoCExt;

class FileAnalyser
{
  private $file;
  private $fileContent;
  private $userDefinedClasses;

  public function __construct($file)
  {
    $this->file = $file; 
    $this->fileContent = file_get_contents($file);
    $this->userDefinedClasses = $this->getUserDefinedClassesInFile($file);
  }

  public function getRootNamespaceOfClass($class)
  {
    //each extension must have a namespace!
    $classComps = explode("\\", $class);
    if (count($classComps) == 1) {
      throw new PHPtoCExtException("class $class should have a namespace!");
    }
    
    $namespace = $classComps[0];

    if ( ctype_upper($namespace) || ctype_lower($namespace) ) {
      throw new PHPtoCExtException("namespace must be in the CamelCase form!");
    }

    return $namespace;
  }

  public function getCodeInClass($className)
  {
    $class = new \ReflectionClass($className);
    $startLine = $class->getStartLine()-1; // getStartLine() seems to start after the {, we want to include the signature
    $endLine = $class->getEndLine();
    $numLines = $endLine - $startLine;
    $namespace = $this->getRootNamespaceOfClass($className);
    $classCode = "namespace $namespace;\n\n".implode("\n",array_slice(explode("\n",$this->fileContent),$startLine,$numLines))."\n";
    return $classCode;
  }

  public function getClassNameWithoutNamespace($className)
  {
    return array_pop(explode("\\", $className));
  }

  public function getUserDefinedClasses()
  {
    return $this->userDefinedClasses;
  }

  private function getUserDefinedClassesInFile() 
  {
    $previousDefinedClasses = get_declared_classes();
    require_once $this->file;
    $currentDefinedClasses = get_declared_classes();
    $userDefinedClasses = array_diff($currentDefinedClasses, $previousDefinedClasses);
    $result = [];
    foreach($userDefinedClasses as $class) {
      $result[] = $class;
    }
    return $result;
  }
}
