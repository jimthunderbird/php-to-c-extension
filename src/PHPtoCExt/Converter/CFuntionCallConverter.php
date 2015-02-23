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
      for ($index = $classInfo->startLine; $index <= $classInfo->endLine; $index++) {
        if (preg_match("/call_c_function\(.*\)/", $this->codeLines[$index], $matches)) {
          if(count($matches) == 1) {
            $codeLine = str_replace(array("call_c_function","(",")",";","$"),"",trim($this->codeLines[$index]));
            $lineComps = explode("=",$codeLine);
            $lineCompsCount = count($lineComps);
            $resultVarName = "";
            if ($lineCompsCount == 1) { //this means we just call the c function and do not have a return variable
              $cFunctionCallComps = explode(",",$lineComps[0]);
              $cFunctionCallComps = explode(",",$lineComps[1]);
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
              $cFunctionCallCode .= "let $resultVarName = null;\n"; //initialize result var
              $cFunctionCallCode .= "\n%{\n";
              if (strlen($resultVarName) == 0) {
                $cFunctionCallCode .= $cFUnctionName."($cFUnctionInputParamsStr);";                 
              } else {
                $cFunctionCallCode .= "$resultVarName = ".$cFUnctionName."($cFUnctionInputParamsStr);";
              }
              $cFunctionCallCode .=  "\n}%\n";

              $expectedZephirCode = 'let '.$resultVarName.' =  call_c_function('.$firstComp.', '.$secondComp.', '.implode(", ",$cFunctionCallComps).');';
              $this->postSearchAndReplace($expectedZephirCode,$cFunctionCallCode);

              //now, inject the c source code to the top of the class 
              $namespace = $classInfo->namespace;
              $classPureName = array_pop(explode("\\",$className));
              $originalCode = "namespace $namespace;\n\n"."class $classPureName\n";
              if (!isset($cSourceCodeMap[$cSourceFile])) {
                $cSourceCode = file_get_contents($this->inputDir."/".$cSourceFile);
                $cSourceCodeMap[$cSourceFile] = $cSourceCode;
                $withCSourceCode = "%{\n".$cSourceCodeMap[$cSourceFile]."\n}%\n".$originalCode; 
                $this->postSearchAndReplace($originalCode, $withCSourceCode);
              }
            }

          }

        }
      }

    }
  }
}
