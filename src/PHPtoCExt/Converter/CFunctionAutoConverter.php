<?php 
namespace PHPtoCExt\Converter;

/**
 * Automatically convert c_function_auto api to corresponding c_function_call apis
 */
class CFunctionAutoConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    //first, find out all call_c_auto calls and find out the corresponding class context this c function call belongs to
    $classMap = $this->getClassMap();

    foreach($classMap as $className => $classInfo) {
      $originalClassCode = implode("\n",array_slice($this->codeLines, $classInfo->startLine - 1, $classInfo->endLine - $classInfo->startLine + 1)); 
      foreach($classInfo->methodInfos as $methodPureName => $methodInfo) {
        for ($index = $methodInfo->startLine; $index <= $methodInfo->endLine; $index++) {
          if (preg_match("/call_c_auto\(.*\)/", $this->codeLines[$index], $matches)) {
            if(count($matches) == 1) {
              $codeLine = $this->codeLines[$index]; //store the original code line here
              $filteredCodeLine = str_replace(array("call_c_auto","(",")",";"),"",trim($codeLine));
              $lineComps = explode("=",$filteredCodeLine);
              $lineCompsCount = count($lineComps);
              $className[0] = "";
              $className = trim($className);
              $classNameComps = explode("\\", $className); 
              //remove namespace 
              array_shift($classNameComps);
              $className = implode("/",$classNameComps);
              if ($lineCompsCount == 1) { //this means we do not have return result variable 
                $inputParamsStr = trim($lineComps[0]);
                if (strlen($inputParamsStr) == 0) {
                  $this->searchAndReplace("call_c_auto();","call_c_function(\"$className.c\",\"$methodPureName\");");
                } else {
                  $this->searchAndReplace($codeLine,"call_c_function(\"$className.c\",\"$methodPureName\",$inputParamsStr);");
                }
              } else if ($lineCompsCount == 2){ //this means we have input return result variable 
                $returnVarStr = $lineComps[0];
                $inputParamsStr = trim($lineComps[1]);
                if (strlen($inputParamsStr) == 0) {
                  $this->searchAndReplace($filteredCodeLine."call_c_auto();","$returnVarStr = call_c_function(\"$className.c\",\"$methodPureName\");");
                } else {
                  $this->searchAndReplace($codeLine,"$returnVarStr = call_c_function(\"$className.c\",\"$methodPureName\",$inputParamsStr);");
                }

              }
            }
          }
        }
      }
    }
  }
} 
