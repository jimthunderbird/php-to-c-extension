<?php 
namespace PHPtoCExt\Converter;

class ModuloCastingConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    foreach($this->codeLines as $index => $line) {
      $moduloSignPos = strpos($line, "%");
      if ($moduloSignPos !== FALSE) {
        //make sure this is not some formatting string used in printf or fprintf 
        $shouldContinue = true;
        for ($i = $moduloSignPos - 1; $i >= 0; $i--) {
          if ($line[$i] == "'" || $line[$i] == '"') {
            $shouldContinue = false;
            break;
          }
        }

        if ($shouldContinue) {
          //now back track the $ sign 
          for ($i = $moduloSignPos - 1; $i >= 0; $i--) {
            if  ($line[$i] == "$") {
              $originalCode = substr($line, $i, $moduloSignPos - $i + 1);
              $convertedCode = "(int)".$originalCode;
              $this->searchAndReplace($originalCode, $convertedCode);
              break;
            }
          }
        } 

      }
    } 
  }
}
