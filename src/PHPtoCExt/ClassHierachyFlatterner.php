<?php 
namespace PHPtoCExt;

class ClassHierachyFlatterner 
{
  private $codeLines;
  private $codeASTXMLLines;

  public function __construct($codeLines, $codeASTXMLLines)
  {
    $this->codeLines = $codeLines;
    $this->codeASTXMLLines = $codeASTXMLLines;
  }

  public function flattern($targetFile)
  {
    $content = file_get_contents($targetFile);

    //get all classes info, with namespace
    $classInfos = array(); 

    $classMap = array();

    $namespace = "";
    $className = "";
      
    foreach($this->codeASTXMLLines as $index => $line)
    {
      if (strpos($line,"<node:Stmt_Namespace>") > 0) {
        $startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 2]);
        $namespace = str_replace(array("namespace ",";"),"",$this->codeLines[$startLine - 1]);
      } else if (strpos($line,"<node:Stmt_Class>") > 0) {
        $classInfo = new \stdClass();
        $classInfo->startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 2]);
        $classInfo->endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 5]);
        $classInfo->namespace = $namespace;
        $classInfo->className = "\\".$namespace."\\".trim(str_replace(array("<scalar:string>","</scalar:string>"),"",$this->codeASTXMLLines[$index + 11])); 
        $classInfo->methods = array();
        $className = $classInfo->className;

        $classInfos[] = $classInfo;   

        $classMap[$classInfo->className] = $classInfo;
      } else if (strpos($line,"<node:Stmt_ClassMethod>") > 0) {
        $classMethodInfo = new \stdClass();
        $classMethodInfo->startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 2]);
        $classMethodInfo->endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 5]);
        $startLineContent = $this->codeLines[$classMethodInfo->startLine - 1];
        $classMethodInfo->name = trim(explode(" ",explode("function ",$startLineContent)[1])[0]);
        //now figure out where it is public, protected or private 

        //find out all methods belongs to this class
        foreach(array("public","protected","private") as $visibility) {
          if (strpos($startLineContent,"$visibility ") !== FALSE) {
            $classMethodInfo->visibility = $visibility;
          }
        }

        if (!isset($classMethodInfo->visibility)) {
          $classMethodInfo->visibility = "protected";
        }

        if (strpos($startLineContent, "static ") !== FALSE) {
          $classMethodInfo->isStatic = true;
        } else {
          $classMethodInfo->isStatic = false;
        }

        $classMap[$className]->methodInfos[] = $classMethodInfo;
      }
    }

    //now figure out the parent classes for each class
    foreach($classInfos as $index => $classInfo) {
      $line = trim($this->codeLines[$classInfo->startLine - 1]);
      if (strpos($line, " extends ") !== FALSE) {
        $lineComps = explode(" extends ", $line);
        $classMap[$classInfo->className]->parentClass = "\\".$classInfo->namespace."\\".trim(explode(" ",$lineComps[1])[0]);
      }
    }

    //now walk through the classMap and flattern out hierarchy 
    foreach($classMap as $className => $classInfo) {
      $currentClassInfo = $classInfo;

      $currentClassCode = implode("\n",array_slice($this->codeLines, $currentClassInfo->startLine - 1, $currentClassInfo->endLine - $currentClassInfo->startLine + 1)); 

      $parentMethodCode = "";
      while(TRUE) {
        if (!isset($currentClassInfo->parentClass)) { 
          break;
        }

        $currentClassEndLine = $currentClassInfo->endLine;

        $currentClassInfo = $classMap[$currentClassInfo->parentClass];

        foreach($currentClassInfo->methodInfos as $methodInfo) {
          $methodCode = implode("\n",array_slice($this->codeLines, $methodInfo->startLine - 1, $methodInfo->endLine - $methodInfo->startLine + 1));
          $convertedMethodCode = str_replace("function ".$methodInfo->name, "function ".str_replace("\\","__",$currentClassInfo->className."_".$methodInfo->name), $methodCode);
          $parentMethodCode .= "\n".$convertedMethodCode."\n";
        }
      }

      if (strlen($parentMethodCode) > 0) {
        $currentClassCodeLines = explode("\n", $currentClassCode);
        $currentClassCodeLines[count($currentClassCodeLines) - 2] .= $parentMethodCode."\n";
        $newClassCode = implode("\n", $currentClassCodeLines);
        $content = str_replace($currentClassCode, $newClassCode, $content);
      }
    }

    print $content;exit();

    file_put_contents($targetFile, $content);  
  }
}
