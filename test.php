<?php

$locale = 'es_PY';
// $locale = 'en_US';
// echo unMaskMoney('USD 82.000');
parseMoney('Gs. 80.000');


function parseMoney($str){
  $str = trim($str);
  $simbolo = substr($str, 0 ,strpos($str, ' '));
  $valor = substr($str, strpos($str, ' ')+1, strlen($str));
  $fmt = new NumberFormatter( 'es_PY', NumberFormatter::DECIMAL );
  return array($simbolo, $valor);
}

