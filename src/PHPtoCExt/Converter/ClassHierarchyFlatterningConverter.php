<?php 
namespace PHPtoCExt\Converter;

/**
 * Flattern the class hierarchy by creating duplicates of methods over the inheritance chain
 */
class ClassHierarchyFlatterningConverter extends \PHPtoCExt\Converter
{
  public function convert() 
  {
    $classMap = $this->getClassMap();

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
      }

      if (strlen($currentParentClass) > 0) {
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
