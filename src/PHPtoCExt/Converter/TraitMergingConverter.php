<?php 
namespace PHPtoCExt\Converter;

/**
 * Merge methods in trait to individual classes
 */
class TraitMergingConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $traitInfoIndexes = array();
    foreach($this->codeASTXMLLines as $index => $line) {
      if (strpos(trim($line), "<node:Stmt_Trait>") === 0) {
        $traitInfoIndexes[] = $index;
      }
      $index ++;
    } 

    $traitBodyMap = array();
    foreach($traitInfoIndexes as $index) {
      $startLineInfo = $this->codeASTXMLLines[$index + 2];
      $endLineInfo = $this->codeASTXMLLines[$index + 5];
      $startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$startLineInfo);
      $endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$endLineInfo);
      $traitName = trim(str_replace(array("<scalar:string>","</scalar:string>"), "", $this->codeASTXMLLines[$index + 8])); 

      $namespace = $this->backtraceNamespaceFromLine($index);
      //now the traitName should consider the namespace it belongs to also 
      $traitName = "\\".$namespace."\\".$traitName;

      $traitCode = trim(implode("\n", array_slice($this->codeLines, $startLine-1, $endLine - $startLine + 1)));
      $traitBodyMap[$traitName] = trim(implode("\n", array_slice($this->codeLines, $startLine + 1, $endLine - $startLine - 2)));

      //remove trait code 
      $this->searchAndReplace($traitCode, "");
    }

    $traitUseIndexes = array();
    foreach($this->codeASTXMLLines as $index => $line) {
      if (strpos(trim($line), "<node:Stmt_TraitUse>") === 0) {
        $traitUseIndexes[] = $index;
      }
      $index ++;
    }

    foreach($traitUseIndexes as $index) {
      $startLineInfo = $this->codeASTXMLLines[$index + 2];
      $endLineInfo = $this->codeASTXMLLines[$index + 5];
      $startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$startLineInfo);
      $endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$endLineInfo);
      $traitUseCode = trim(implode("\n", array_slice($this->codeLines, $startLine-1, $endLine - $startLine + 1)));
      $traitsUsing = array_map(function($element){
        return trim($element); 
      },explode(",",str_replace(array("use ",";"),"",$traitUseCode)));

      $traitActualCode = "";
      foreach($traitsUsing as $traitName) {

        if ($traitName[0] !== "\\") { //this is referencing a trait with relative namespace 
          $namespace = $this->backtraceNamespaceFromLine($index);
          $traitName = "\\".$namespace."\\".$traitName;
        }

        if (!isset($traitBodyMap[$traitName])) {
          throw new \PHPtoCExt\PHPtoCExtException("using undefined trait ".$traitName." in code: ".$traitUseCode);
        } 

        $traitActualCode .= $traitBodyMap[$traitName]."\n";
      }

      $this->searchAndReplace($traitUseCode, $traitActualCode);

    }

  }

  private function backtraceNamespaceFromLine($line)
  {
    $result = "";

    //now we have the traitName, we will go back and search for the namespace that this trait belongs to
    for ($i = $line - 1; $i >= 0; $i--) {
      if (strpos(trim($this->codeASTXMLLines[$i]),"<node:Stmt_Namespace>") === 0) {
        $namespaceStartLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$this->codeASTXMLLines[$i + 2]);
        $result = trim(str_replace(array("namespace ",";"),"",$this->codeLines[$namespaceStartLine - 1]));
        break;
      }
    }

    return $result;
  }  
}
