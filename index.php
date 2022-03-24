<?php

require_once __DIR__.'/spreadsheet/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
include __DIR__.'/models/property_model.php';
include __DIR__.'/network/curl.php';
include __DIR__.'/models/estados_model.php';

date_default_timezone_set('America/Asuncion');

$inputFileName = __DIR__ . '/datos.xlsx';
$spreadsheet = IOFactory::load($inputFileName);
$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

$property = new PropertyModel();
// var_dump($sheetData);

foreach($sheetData as $row => $column){
  if($row != 1){
   $property->fechaIngreso = $column['A'];
   $property->tipoInmueble = $column['B'];
   if($column['C'] == ''){
     
   }
  //  $property-> = $value[''];
  //  $property-> = $value[''];
  //  $property-> = $value[''];
  //  $property-> = $value[''];
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
  // $json = json_encode($data);
  // $object = json_decode($json);
  $json = json_encode($data, JSON_PRETTY_PRINT);
  echo "<pre>$json</pre>";
}



// $data = array(
//   'id' => 1,
//   'title' => 'foo',
//   'body' => 'bar',
//   'userId' => 1,
// );
