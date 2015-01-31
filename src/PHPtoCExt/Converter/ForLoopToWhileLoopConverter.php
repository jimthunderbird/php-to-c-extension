<?php 
namespace PHPtoCExt\Converter;

class ForLoopToWhileLoopConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $forLoopInfoIndexes = array();
    $index = 0;
    foreach($this->codeASTXMLLines as $line) {
      if (strpos(trim($line), "<node:Stmt_For>") === 0) {
        $forLoopInfoIndexes[] = $index;
      }
      $index ++;
    }

    $forLoopInfos = array();
    foreach($forLoopInfoIndexes as $index) {
      $startLineInfo = $this->codeASTXMLLines[$index + 2];
      $endLineInfo = $this->codeASTXMLLines[$index + 5];
      $forLoopInfo = new \stdClass();
      $forLoopInfo->startLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$startLineInfo);
      $forLoopInfo->endLine = (int)str_replace(array("<scalar:int>","</scalar:int>"),"",$endLineInfo);
      $forLoopInfo->originalCode = trim(implode("\n", array_slice($this->codeLines, $forLoopInfo->startLine-1, $forLoopInfo->endLine - $forLoopInfo->startLine + 1)));
      $forLoopInfo->bodyCode = trim(implode("\n",array_slice($this->codeLines, $forLoopInfo->startLine, $forLoopInfo->endLine - $forLoopInfo->startLine - 1)));
      $forLoopStartLineCode = $this->codeLines[$forLoopInfo->startLine - 1];
      $forLoopIndentationCode = explode("for",$forLoopStartLineCode)[0];
      preg_match('/(?<=\()(.+)(?=\))/is', $forLoopStartLineCode, $match);
      $forLoopParenthesisCode = $match[0];
      $forLoopParenthesisCodeComps = explode(";", $forLoopParenthesisCode);
      $forLoopInfo->initCode = trim($forLoopParenthesisCodeComps[0]);
      $forLoopInfo->conditionCode = trim($forLoopParenthesisCodeComps[1]);
      $forLoopInfo->incrementalCode = trim($forLoopParenthesisCodeComps[2]);
      $convertedWhileLoopCode = $forLoopInfo->initCode
        .";\n"
        .$forLoopIndentationCode
        ."while({$forLoopInfo->conditionCode}){\n"
        .$forLoopIndentationCode 
        .$forLoopIndentationCode
        .$forLoopInfo->bodyCode."\n"
        .$forLoopIndentationCode 
        .$forLoopIndentationCode 
        .$forLoopInfo->incrementalCode.";\n"
        .$forLoopIndentationCode
        ."}\n";
      $forLoopInfo->convertedWhileLoopCode = $convertedWhileLoopCode; 
      $forLoopInfos[] = $forLoopInfo;

    }

    foreach($forLoopInfos as $forLoopInfo) {
      $this->searchAndReplace($forLoopInfo->originalCode, $forLoopInfo->convertedWhileLoopCode);
    }
  } 
}
