<?php

require_once __DIR__.'/spreadsheet/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
include __DIR__.'/models/property_model.php';
include __DIR__.'/network/curl.php';
include __DIR__.'/models/estados_model.php';
include __DIR__.'/models/property_type_model.php';

date_default_timezone_set('America/Asuncion');

$inputFileName = __DIR__ . '/datos.xlsx';
$spreadsheet = IOFactory::load($inputFileName);
$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);



foreach($sheetData as $row => $column){
  $property = new PropertyModel();
  $tipoInmueble = new PropertyTypeModel();
  
  if($row != 1){
    $data = nameData($column);
    $property->fechaIngreso = $data['fechaIngreso'];
    $precioAlquiler = null;
    $precioVenta = null;
    $isVenta = false;
    $isAlquiler = false;
    
    if($data['precioAlquilerSm'] != ''){
      $parse = parseMoney($data['precioAlquilerSm']);
      $precioAlquiler = $parse[0];
      $isAlquiler = true;
    }elseif($data['precioAlquilerCm'] != ''){
      $parse = parseMoney($data['precioAlquilerCm']);
      $precioAlquiler = $parse[0];
      $isAlquiler = true;
    }
    if($data['precioVentaSm'] != ''){
      $parse = parseMoney($data['precioVentaSm']);
      $precioVenta = $parse[0];
      $isVenta = true;
    }elseif($data['precioVentaCm'] != ''){
      $parse = parseMoney($data['precioVentaCm']);
      $precioVenta = $parse[0];
      $isVenta = true;
    }
    
    $property->precioAlquiler = $precioAlquiler;
    $property->precioVenta = $precioVenta;
    $tipoInmueble->nombre = $data['tipoInmueble'];
    $tipoInmueble->alquiler = $isAlquiler;
    $tipoInmueble->venta = $isVenta;
  }
}



function saveProperty($data){
  // $url = 'https://sai.propiver.com/SAI/seam/resource/rest/inmuebles/save';
  $url = 'https://jsonplaceholder.typicode.com/posts/1';
  $curl = new Curl();
  $curl->url($url);
  $curl->method('put');
  $curl->data($data);
  $response = $curl->send();
  jsonPretty($response);
}

function displayEcho(){
  ob_implicit_flush(true);
  ob_end_flush();
}

function jsonPretty($data){
  $json = json_encode($data, JSON_PRETTY_PRINT);
  echo "<pre>$json</pre>";
}

function parseMoney($str){
  $str = trim($str);
  $simbolo = substr($str, 0 ,strpos($str, ' '));
  $valor = substr($str, strpos($str, ' ')+1, strlen($str));
  $fmt = new NumberFormatter( 'es_PY', NumberFormatter::DECIMAL );
  return array($valor, $simbolo);
}

function nameData($c){
  return array(
    'fechaIngreso' => $c['A'],
    'precioAlquilerSm' => $c['C'],
    'precioAlquilerCm' => $c['D'],
    'precioVentaSm' => $c['E'],
    'precioVentaCm' => $c['F'],
    'tipoInmueble' => $c['G']
  );
}