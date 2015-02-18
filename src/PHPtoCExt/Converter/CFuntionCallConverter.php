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

    foreach($classMap as $className => $classInfo) {
      for ($index = $classInfo->startLine; $index <= $classInfo->endLine; $index++) {
        if (preg_match("/call_c_function\(.*\)/", $this->codeLines[$index], $matches)) {
          if(count($matches) == 1) {
            $codeLine = str_replace(array("call_c_function","(",")",";"),"",trim($this->codeLines[$index]));
            $lineComps = explode("=",$codeLine);
            $lineCompsCount = count($lineComps);
            if ($lineCompsCount == 1) { //this means we just call the c function and do not have a return variable
              $cFunctionCallComps = explode(",",$lineComps[0]);
            } else if ($lineCompsCount > 1) { //this means we call the c function and then store the result in a return variable 
              $resultVarName = trim(str_replace("$","",$lineComps[0]));
              $cFunctionCallComps = explode(",",$lineComps[1]);
              foreach($cFunctionCallComps as $idx => $comp) {
                $cFunctionCallComps[$idx] = trim(str_replace(array("'",'"',"$"),"",$cFunctionCallComps[$idx]));
              }
              print_r($cFunctionCallComps);exit();
            }
          }
        }
      }
    }

  }
}
