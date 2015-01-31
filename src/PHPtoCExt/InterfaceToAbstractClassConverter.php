<?php 
namespace PHPtoCExt;

class InterfaceToAbstractClassConverter extends Converter
{
  public function convert()
  {
    $codeLines = explode("\n", $this->code);

    $parser = new \PhpParser\Parser(new \PhpParser\Lexer);
    $serializer = new \PhpParser\Serializer\XML();

    try {
      $stmts = $parser->parse($this->code);

      $codeLines = explode("\n", $this->code);
      $codeASTXML = $serializer->serialize($stmts);
      $codeASTXMLLines = explode("\n", $codeASTXML);

      $interfaceInfoIndexes = array();
      foreach($codeASTXMLLines as $index => $line) {
        if (strpos(trim($line), "<node:Stmt_Interface>") === 0) {
          $interfaceInfoIndexes[] = $index;
        }  
      }

      foreach($interfaceInfoIndexes as $index) {
        $startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$codeASTXMLLines[$index + 2]);
        $endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$codeASTXMLLines[$index + 5]);
        $codeLines[$startLine - 1] = str_replace("interface ","abstract class ",$codeLines[$startLine - 1]);
        for ($i = $startLine + 1; $i < $endLine - 1; $i++) {
          if(strlen(trim($codeLines[$i])) > 0) {
            $codeLines[$i] = "abstract ".$codeLines[$i];
          } 
        }
      }

      $this->code = implode("\n",$codeLines);

      //replace implements with extends 
      $this->code = str_replace(" implements ", " extends ", $this->code);
      
    } catch (\PhpParser\Error $e) {
      throw new PHPtoCExtException("PHP Parser Error: ".$e->getMessage());
    }


    return $this->code;
  }
}
