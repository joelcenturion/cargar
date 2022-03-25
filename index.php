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
$inmueble = new stdClass();

foreach($sheetData as $row => $column){
  $property = new PropertyModel();
  $tipoInmueble = new PropertyTypeModel();
  
  if($row != 1){
    $data = nameData($column);
    $fechaIngreso = date('Y-m-d', strtotime($data['fechaIngreso']));
    $property->fechaIngreso = $fechaIngreso;
    $descripcion = $data['descripcion'];
    $length = (strlen($descripcion) < 200) ? strlen($descripcion) : 200;
    $property->titulo = substr($descripcion, 0, $length);
    $property->descripcion = $descripcion;
    $precioAlquiler = 0;
    $precioVenta = 0;
    $isVenta = false;
    $isAlquiler = false;
    $monedaVenta = '$';
    $monedaAlquiler = 'Gs.';
    
    if(!empty($data['precioAlquilerSm'])){
      $parse = parseMoney($data['precioAlquilerSm']);
      $precioAlquiler = $parse[0];
      $monedaAlquiler = $parse[1];
      $isAlquiler = true;
    }elseif(!empty($data['precioAlquilerCm'])){
      $parse = parseMoney($data['precioAlquilerCm']);
      $precioAlquiler = $parse[0];
      $isAlquiler = true;
      $monedaAlquiler = $parse[1];
    }
    if(!empty($data['precioVentaSm'])){
      $parse = parseMoney($data['precioVentaSm']);
      $precioVenta = $parse[0];
      $isVenta = true;
      $monedaVenta = $parse[1];
    }elseif(!empty($data['precioVentaCm'])){
      $parse = parseMoney($data['precioVentaCm']);
      $precioVenta = $parse[0];
      $isVenta = true;
      $monedaVenta = $parse[1];
    }
    
    $property->precioAlquiler = (float) $precioAlquiler;
    $property->precioVenta = (float) $precioVenta;
    $property->monedaAlquiler = $monedaAlquiler;
    $property->monedaVenta = $monedaVenta;
    $tipoInmueble->nombre = strtoupper($data['tipoInmueble']);
    $tipoInmueble->alquiler = $isAlquiler;
    $tipoInmueble->venta = $isVenta;
    $property->tipoInmueble = $tipoInmueble;
    
    $inmueble->inmueble = $property;
    saveProperty($inmueble);
    // jsonPretty($inmueble);
    // var_dump($inmueble);
    // echo json_encode($inmueble);
  }
}

// var_dump($property);
// jsonPretty($property);


function saveProperty($data){
  $url = 'https://sai.propiver.com/SAI/seam/resource/rest/inmuebles/save';
  // $url = 'https://jsonplaceholder.typicode.com/posts/1';
  $curl = new Curl();
  $curl->url($url);
  $curl->method('put');
  $curl->data($data);
  $response = $curl->send();
  jsonPretty(json_decode($response));
  $curl->close();
}

function displayEcho(){
  ob_implicit_flush(true);
  ob_end_flush();
}

function jsonPretty($data){
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION);
  echo "<pre>$json</pre>";
}

function parseMoney($str){
  $fmt = new NumberFormatter( 'es_PY', NumberFormatter::DECIMAL );
  $str = trim($str);
  $simbolo = trim(substr($str, 0 ,strpos($str, ' ')));
  if(strcasecmp($simbolo, 'USD') == 0){
    $simbolo = '$';
  }
  $valor = substr($str, strpos($str, ' ')+1, strlen($str));
  return array($fmt->parse($valor), $simbolo);
}

function nameData($c){
  return array(
    'fechaIngreso' => trim($c['A']),
    'precioAlquilerSm' => trim($c['C']),
    'precioAlquilerCm' => trim($c['D']),
    'precioVentaSm' => trim($c['E']),
    'precioVentaCm' => trim($c['F']),
    'tipoInmueble' => trim($c['G']),
    'descripcion' => trim($c['H'])
  );
}