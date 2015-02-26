<?php 
/**
 * convert c function call to actual embedded c code and c function invoking code in zephir 
 * c function call format:
 * result = call_c_function([c source file], [c function name], [input var 1],[input var 2],...);
 */
namespace PHPtoCExt\Converter;

class CFuntionCallConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    //first, find out all call_c_function calls and find out the corresponding class context this c function call belongs to
    $cFunctionCallIndexes = array();

    $classMap = $this->getClassMap();

    $cSourceCodeMap = array();

    foreach($classMap as $className => $classInfo) {
      $originalClassCode = implode("\n",array_slice($this->codeLines, $classInfo->startLine - 1, $classInfo->endLine - $classInfo->startLine + 1)); 
      foreach($classInfo->methodInfos as $methodPureName => $methodInfo) {
        $tmpCFuncCallResultDefined = false;
        for ($index = $methodInfo->startLine; $index <= $methodInfo->endLine; $index++) {
          if (preg_match("/call_c_function\(.*\)/", $this->codeLines[$index], $matches)) {

            //we simply add $tmpCFuncCallResult = null;\n at the beginning of the method 
            if (!$tmpCFuncCallResultDefined) {
              $this->codeLine[$index] = "\$tmpCFuncCallResult = null\n".$this->codeLines[$index];
              $tmpCFuncCallResultDefined = true;
            } 

            if(count($matches) == 1) {
              $codeLine = str_replace(array("call_c_function","(",")",";","$"),"",trim($this->codeLines[$index]));
              $lineComps = explode("=",$codeLine);
              $lineCompsCount = count($lineComps);
              $resultVarName = "";
              if ($lineCompsCount == 1) { //this means we just call the c function and do not have a return variable
                $cFunctionCallComps = explode(",",$lineComps[0]);
                foreach($cFunctionCallComps as $idx => $comp) {
                  $cFunctionCallComps[$idx] = trim(str_replace("$","",$cFunctionCallComps[$idx]));
                }
              } else if ($lineCompsCount > 1) { //this means we call the c function and then store the result in a return variable 
                $resultVarName = trim(str_replace("$","",$lineComps[0]));
                $cFunctionCallComps = explode(",",$lineComps[1]);
                foreach($cFunctionCallComps as $idx => $comp) {
                  $cFunctionCallComps[$idx] = trim(str_replace("$","",$cFunctionCallComps[$idx]));
                }
              }

              if (count($cFunctionCallComps) > 0) {
                $firstComp = array_shift($cFunctionCallComps);
                $cSourceFile = str_replace(array("'",'"'),"",$firstComp);
                $secondComp = array_shift($cFunctionCallComps);
                $cFUnctionName = str_replace(array("'",'"'),"",$secondComp);
                $cFUnctionInputParamsStr = "";
                if (count($cFunctionCallComps) > 0) {
                  $cFUnctionInputParamsStr = implode(", ",$cFunctionCallComps); 
                }

                $cFunctionCallCode = "";
                if (strlen($resultVarName) == 0) {
                  $cFunctionCallCode .= "\n%{\n";
                  $cFunctionCallCode .= $cFUnctionName."($cFUnctionInputParamsStr);";                 
                  $cFunctionCallCode .=  "\n}%\n";
                  if (strlen($cFUnctionInputParamsStr) == 0) { 
                    $expectedZephirCode = 'call_c_function('.$firstComp.', '.$secondComp.');';
                  } else {
                    $expectedZephirCode = 'call_c_function('.$firstComp.', '.$secondComp.', '.implode(", ",$cFunctionCallComps).');';
                  }
                } else {
                  $cFunctionCallCode .= "let $resultVarName = null;\n"; //initialize result var
                  $cFunctionCallCode .= "\n%{\n";
                  $cFunctionCallCode .= "tmpCFuncCallResult = ".$cFUnctionName."($cFUnctionInputParamsStr);\n";
                  $cFunctionCallCode .=  "\n}%\n";
                  $cFunctionCallCode .= "let $resultVarName = tmpCFuncCallResult;\n";
                  if (strlen($cFUnctionInputParamsStr) == 0) {
                    $expectedZephirCode = 'let '.$resultVarName.' =  call_c_function('.$firstComp.', '.$secondComp.');';
                  } else {
                    $expectedZephirCode = 'let '.$resultVarName.' =  call_c_function('.$firstComp.', '.$secondComp.', '.implode(", ",$cFunctionCallComps).');';
                  }
                }

                $this->postSearchAndReplace($expectedZephirCode,$cFunctionCallCode);

                //now, inject the c source code to the top of the class 
                $namespace = $classInfo->namespace;
                $classPureName = array_pop(explode("\\",$className));
                $originalCode = "namespace $namespace;\n\n"."class $classPureName\n";
                //make sure we only have one unique copy of c source file per class
                $classNameCSourceFileKey = $className.".".$cSourceFile;
                if (!isset($cSourceCodeMap[$classNameCSourceFileKey])) {
                  $cSourceCode = file_get_contents($this->inputDir."/".$cSourceFile);
                  $cSourceCodeMap[$classNameCSourceFileKey] = $cSourceCode;
                  $withCSourceCode = "%{\n".$cSourceCodeMap[$classNameCSourceFileKey]."\n}%\n".$originalCode; 
                  $this->postSearchAndReplace($originalCode, $withCSourceCode);
                }
              }

            }

          }
        }

      }
      
      $currentClassCode = implode("\n",array_slice($this->codeLines, $classInfo->startLine - 1, $classInfo->endLine - $classInfo->startLine + 1)); 
      $this->searchAndReplace($originalClassCode, $currentClassCode);
    }
  }
}
