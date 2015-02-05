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
    $this->requireFile($file);
  }

  public function getNamespaceOfClass($class)
  {
    $classComps = explode("\\", $class);
    if (count($classComps) == 1) {
      throw new PHPtoCExtException("class $class should have a namespace!");
    }
    array_pop($classComps);
    $namespace = "";
    foreach($classComps as $index => $comp) {
      if ( ctype_upper($comp) || ctype_lower($comp) ) {
        throw new PHPtoCExtException("namespace must be in the CamelCase form!");
      }

      if ($index == 0) {
        $namespace .= $comp;
      } else {
        $namespace .= "\\".$comp;
      }
    }
    return $namespace;
  }

  public function getRootNamespaceOfClass($class)
  {
    $namespace = $this->getNamespaceOfClass($class);
    $rootNamespace = array_shift(explode("\\",$namespace));
    return $rootNamespace;
  }

  public function getCodeInClass($className)
  {
    $class = new \ReflectionClass($className);
    $startLine = $class->getStartLine()-1; // getStartLine() seems to start after the {, we want to include the signature
    $endLine = $class->getEndLine();
    $numLines = $endLine - $startLine;
    $namespace = $this->getNamespaceOfClass($className);
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

  private function requireFile($file)
  {
    $previousDefinedClasses = get_declared_classes();
    $previousDefinedInterfaces = get_declared_interfaces();
    require_once $this->file;
    $currentDefinedClasses = get_declared_classes();
    $currentDefinedInterfaces = get_declared_interfaces();

    $userDefinedClasses = array_diff($currentDefinedClasses, $previousDefinedClasses);
    $userDefinedInterfaces = array_diff($currentDefinedInterfaces, $previousDefinedInterfaces);

    //now treat classes and interfaces as the same 
    $this->userDefinedClasses = array_merge($userDefinedClasses, $userDefinedInterfaces); 
  }
}
