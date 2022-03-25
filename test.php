<?php

$n = 'USD 82.000';
ini_set('precision', -1);
// ini_set('serialize_precision',2);
$obj =  new stdClass();
$obj->precioVenta =  123.00; 
echo json_encode($obj, JSON_PRESERVE_ZERO_FRACTION);
// parseMoney($n);


function parseMoney($str){
  $fmt = new NumberFormatter( 'es_PY', NumberFormatter::DECIMAL );
  $str = trim($str);
  $simbolo = substr($str, 0 ,strpos($str, ' '));
  $valor = substr($str, strpos($str, ' ')+1, strlen($str));
  $valor = $fmt->parse($valor);
  echo $valor.'  ';
  $valor = number_format($valor, 2, ',', '');
  echo $valor;
  echo $fmt->parse($valor);
  // echo json_encode(array($fmt->parse($valor), $simbolo));
}

