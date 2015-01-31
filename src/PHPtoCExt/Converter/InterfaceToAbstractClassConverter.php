<?php 
namespace PHPtoCExt\Converter;

class InterfaceToAbstractClassConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $interfaceInfoIndexes = array();
    foreach($this->codeASTXMLLines as $index => $line) {
      if (strpos(trim($line), "<node:Stmt_Interface>") === 0) {
        $interfaceInfoIndexes[] = $index;
      }  
    }

    foreach($interfaceInfoIndexes as $index) {
      $startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 2]);
      $endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 5]);

      $originalCode = implode("\n",array_slice($this->codeLines, $startLine - 1, $endLine - $startLine + 1));

      $convertedCode = "";
      $convertedCode .= str_replace("interface ","abstract class ",$this->codeLines[$startLine - 1]);
      $convertedCode .= "{\n";
      for ($i = $startLine + 1; $i < $endLine - 1; $i++) {
        if(strlen(trim($this->codeLines[$i])) > 0) {
          $convertedCode .= "abstract ".trim($this->codeLines[$i])."\n";
        }
      }
      $convertedCode .= "}\n";
      
      $this->searchAndReplace($originalCode, $convertedCode);
    }

    //replace implements with extends 
    $this->searchAndReplace(" implements "," extends ");

  }
}
