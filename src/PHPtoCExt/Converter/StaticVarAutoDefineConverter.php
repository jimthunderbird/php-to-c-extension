<?php 
namespace PHPtoCExt\Converter;

class StaticVarAutoDefineConverter extends \PHPtoCExt\Converter
{
  public function convert()
  {
    $classMap = $this->GetClassMap();
    foreach($classMap as $className => $classInfo) {

      $classPropertyStartLine = $classInfo->startLine + 1;
      $classPropertyEndLine = $classInfo->endLine - 1;

      $classCode = implode("\n",array_slice($this->codeLines, $classInfo->startLine - 1, $classInfo->endLine - $classInfo->startLine + 1)); 

      $staticVars = array();

      $i = 0;
      foreach($classInfo->methodInfos as $methodPureName => $methodInfo)
      {
        $i++;

        if ($i == 1) {
          $classPropertyEndLine = $methodInfo->startLine - 1;
        }

        $methodCode = implode("\n",array_slice($this->codeLines, $methodInfo->startLine - 1, $methodInfo->endLine - $methodInfo->startLine + 1)); 
        preg_match_all("/self::\\$[a-zA-Z_]+/", $methodCode, $matches);

        if (count($matches) > 0 && count($matches[0]) > 0) {
          $staticVars = array_unique($matches[0]);
          $staticVars = array_map(function($element){
            $element = str_replace("self::$","",$element);
            return $element;
          }, $staticVars);
        }

      }

      $definedStaticVars = array();
      for ($j = $classPropertyStartLine; $j <= $classPropertyEndLine; $j++) {
        $line = $this->codeLines[$j-1];
        if (strpos($line, "static ") !== FALSE) {
          $definedStaticVars[] = str_replace("$","",trim(explode("=",explode("static ", $line)[1])[0]));
        }
      }

      $undefinedStaticVars = array();
      $undefinedStaticVars = array_diff($staticVars, $definedStaticVars);

      $defineStaticVarStmt = "";
      foreach($undefinedStaticVars as $var) {
        //by default, just auto define variables as protected 
        $defineStaticVarStmt .= "protected static $".$var.";\n";  
      }

      $this->codeLines[$classPropertyStartLine - 1] .= "\n".$defineStaticVarStmt."\n";

      $newClassCode = implode("\n",array_slice($this->codeLines, $classInfo->startLine - 1, $classInfo->endLine - $classInfo->startLine + 1));

      $this->searchAndReplace($classCode, $newClassCode);
    }
  }
}
