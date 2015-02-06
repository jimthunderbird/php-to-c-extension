<?php 
namespace PHPtoCExt\Converter;

/**
 * Flattern the class hierarchy by creating duplicates of methods over the inheritance chain
 */
class ClassHierarchyFlatterningConverter extends \PHPtoCExt\Converter
{
  public function convert() 
  {
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
        $classInfo->methodInfos = array();
        $className = $classInfo->className;

        $classInfos[] = $classInfo;   

        $classMap[$classInfo->className] = $classInfo;
      } else if (strpos($line,"<node:Stmt_ClassMethod>") > 0) {
        $classMethodInfo = new \stdClass();
        $classMethodInfo->startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 2]);
        $classMethodInfo->endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 5]);
        $startLineContent = $this->codeLines[$classMethodInfo->startLine - 1];
        $classMethodInfo->name = trim(explode(" ",explode("function ",$startLineContent)[1])[0]);
        $classMethodInfo->pureName = explode(" ", str_replace("(", " ", $classMethodInfo->name))[0];
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

        $classMap[$className]->methodInfos[$classMethodInfo->pureName] = $classMethodInfo;
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

      $currentClassMethodInfos = $currentClassInfo->methodInfos;

      $currentParentClass = isset($currentClassInfo->parentClass)?$currentClassInfo->parentClass:null;

      $injectedCode = "";

      while(TRUE) {
        if (!isset($currentClassInfo->parentClass)) { 
          break;
        }

        $currentClassEndLine = $currentClassInfo->endLine;

        $parentClassInfo = $classMap[$currentClassInfo->parentClass]; //point current class info to the parent one 
 
        foreach($parentClassInfo->methodInfos as $methodPureName => $methodInfo) {
          $methodCode = implode("\n",array_slice($this->codeLines, $methodInfo->startLine - 1, $methodInfo->endLine - $methodInfo->startLine + 1)); 

          $selfReference = $methodInfo->isStatic?"\$self::":"\$this->";

          $convertedMethodCode = $methodCode;

          if (!isset($currentClassMethodInfos[$methodPureName])) { //the current class does not have method defined, grab the parent version 
            $currentClassMethodInfos[$methodPureName] = $methodCode;
            //now replace parent:: to __[namespace components]
            if (isset($parentClassInfo->parentClass)) {
              $convertedMethodCode = str_replace("parent::",$selfReference.strtolower(str_replace("\\","__",$parentClassInfo->parentClass))."_", $methodCode);
            }
            $injectedCode .= "\n".$convertedMethodCode."\n"; 
          } 

          $convertedMethodCode = str_replace("function ".$methodInfo->name, "function ".strtolower(str_replace("\\","__",$parentClassInfo->className)."_".$methodInfo->name), $methodCode);
          //now replace parent:: to __[namespace components] 
          if (isset($parentClassInfo->parentClass)) {
            $convertedMethodCode = str_replace("parent::",$selfReference.strtolower(str_replace("\\","__",$parentClassInfo->parentClass))."_", $convertedMethodCode);
          }

          $injectedCode.= "\n".$convertedMethodCode."\n";

        }

        $currentClassInfo = $parentClassInfo;
      }

      $newClassCode = $currentClassCode;
      if (strlen($injectedCode) > 0) {
        $currentClassCodeLines = explode("\n", $currentClassCode);
        $currentClassCodeLines[count($currentClassCodeLines) - 2] .= $injectedCode."\n";
        $newClassCode = implode("\n", $currentClassCodeLines);
        //we still need to convert parent:: to $selfReference 
        $newClassCode = str_replace("parent::",$selfReference.strtolower(str_replace("\\","__",$currentParentClass))."_", $newClassCode);
      }

      //convert all static to self      
      $newClassCode = str_replace("static::","self::", $newClassCode);

      $this->searchAndReplace($currentClassCode, $newClassCode);

      //finally, in the zephir code, we need to replace {self}:: to self::
      $this->postSearchAndReplace("{self}::","self::");
      $this->postSearchAndReplace(" static()"," self()");

    }
  }
}
