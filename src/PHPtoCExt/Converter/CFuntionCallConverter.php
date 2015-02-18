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
            $lineComps = explode("=",$this->codeLines[$index]);
            $lineCompsCount = count($lineComps);
            if ($lineCompsCount == 1) { //this means we just call the c function and do not have a return variable
              
            } else if ($lineCompsCount == 2) { //this means we call the c function and then store the result in a return variable

            }
          }
        }
      }
    }

  }
}
