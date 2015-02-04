<?php
namespace PHPtoCExt\Converter;

class SelfStaticConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $classMethodInfoIndexes = array();
    foreach($this->codeASTXMLLines as $index => $line) {
      if (strpos(trim($line), "<node:Stmt_ClassMethod>") === 0) {
        $classMethodInfoIndexes[] = $index;
      }
    }

    //add post convertion searches and replaces 
    //tricky, this must come first
    $this->postSearchAndReplace("var self__static__instance;","");
    $this->postSearchAndReplace("let self__static__instance =  null;","");


    foreach($classMethodInfoIndexes as $index) {
      $selfStaticVarNamesMap = array();
      $startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 2]);
      $endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$index + 5]);
      //now search for all self::$ pattern
      for ($i = $startLine + 1; $i <= $endLine - 1; $i++) {
        //we need to find out the self static variable name here! 
        preg_match("/self::\\$[a-zA-Z_]+/", $this->codeLines[$i], $matches);
        if (count($matches) == 1) {
          $selfStaticVarName = str_replace(array("self::","$"),"",$matches[0]);
          $selfStaticVarNamesMap[$selfStaticVarName] = 1;
        }
      }
      if (count($selfStaticVarNamesMap) > 0) {
        $originalClassMethodCode = trim(implode("\n", array_slice($this->codeLines, $startLine-1, $endLine - $startLine + 1)));
        foreach($selfStaticVarNamesMap as $varName => $value) {
          //now we will construct the static var init statement 
          $selfStaticVarNameInitStmt = "\n"."$"."self__static__".$varName."=null;\n";  
          $this->codeLines[$startLine] .= $selfStaticVarNameInitStmt;
          //add post convertion searches and replaces 
          $this->postSearchAndReplace("self__static__".$varName,"self::".$varName);
        } 
        $convertedClassMethodCode = trim(implode("\n", array_slice($this->codeLines, $startLine-1, $endLine - $startLine + 1)));
        $convertedClassMethodCode = str_replace("self::$","\$self__static__", $convertedClassMethodCode);
        $this->searchAndReplace($originalClassMethodCode, $convertedClassMethodCode);
      }
    }
    
  }
}

