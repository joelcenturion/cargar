<?php
displayEcho();
require_once __DIR__.'/spreadsheet/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once __DIR__.'/log.php';
require_once __DIR__.'/models/property_model.php';
require_once __DIR__.'/network/curl.php';
require_once __DIR__.'/models/estados_model.php';
require_once __DIR__.'/models/property_type_model.php';
require_once __DIR__.'/models/archivo_model.php';
require_once __DIR__.'/models/estados_model.php';

date_default_timezone_set('America/Asuncion');

$inputFileName = __DIR__ . '/datos.xlsx';
$imagesPath = __DIR__.'/images';

$spreadsheet = IOFactory::load($inputFileName);
$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
$inmueble = new stdClass();

foreach($sheetData as $row => $column){
    
  if($row != 1){
    $property = new PropertyModel();
    $tipoInmueble = new PropertyTypeModel();  
    $data = getData($column);
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
    $estado = new Estado();
    $estado->estado = strtoupper((!empty($data['estado']) ? $data['estado'] : 'DISPONIBLE'));
    $estado->fechaHora = $fechaIngreso;
    $property->estados = array($estado);
    
    $inmueble->inmueble = $property;
    // saveProperty($inmueble, $imagesPath, $data['linkDrive'], $row);
    jsonPretty($inmueble);
    // var_dump($inmueble);
  }
}

// var_dump($property);
// jsonPretty($property);


function saveProperty($data, $imagesPath, $linkDrive, $row){
  $url = 'https://sai.propiver.com/SAI/seam/resource/rest/inmuebles/save';
  // $url = 'https://jsonplaceholder.typicode.com/posts/1';
  $curl = new Curl();
  $curl->url($url);
  $curl->method('put');
  $curl->data($data);
  try{
    $response = $curl->send();
    $response = json_decode($response);
    jsonPretty($response);
    if(isset($response->id)){
      if(!empty($linkDrive)){
        $archivos = assembleArchivos($linkDrive, $imagesPath, $response->id);
        if (!($archivos ===false)){
          saveAlbum($archivos, $response->id, $row);
        }
      }
    }else{
      $ex = "Failed: $row\nResponse: ".json_encode($response, JSON_PRETTY_PRINT);
      writeOnLog($ex);
    }
  }catch(Exception $ex){
    $ex = "Fila: $row \n$ex";
    echo($ex);
    writeOnLog($ex);
  }
  $curl->close();
}

function displayEcho(){
  ob_implicit_flush(true);
  ob_end_flush();
}

function jsonPretty($data){
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
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

function getData($c){
  return array(
    'fechaIngreso' => trim($c['A']),
    'precioAlquilerSm' => trim($c['C']),
    'precioAlquilerCm' => trim($c['D']),
    'precioVentaSm' => trim($c['E']),
    'precioVentaCm' => trim($c['F']),
    'tipoInmueble' => trim($c['B']),
    'descripcion' => trim($c['G']),
    'linkDrive' => trim($c['I']),
    'estado' => trim($c['J']),
  );
}

function getLinkId($link){
  if(!(strpos($link, 'folders/') === false)){
    $needle = 'folders/';
    $id = substr($link, stripos($link, $needle) + strlen($needle));
    if(strpos($id,'?')!==false){
      $id =  substr($id, 0, strpos($id, '?'));
    }
  }else{
    $needle = 'file/d/';
    $id = substr($link, stripos($link, $needle) + strlen($needle));
    $id =  substr($id, 0, stripos($id, '/view'));
  }
  return $id;
}

function assembleArchivos($link, $imagesPath, $idInmueble){
  $driveId = getLinkId($link);
  $dir = "$imagesPath/$driveId";
  if(is_dir($dir)){
    $fileList = array_diff(scandir($dir), array('.', '..'));
    $archivos = array();
    foreach($fileList as $file){
      $archivo = new Archivo();
      $archivo->nombre = $file;
      $archivo->mimeType = 'image/'.(substr($file, strpos($file, '.') + 1, strlen($file) - strpos($file, '.') - 1));
      $archivo->pathArchivo = '/SAI/Documentos/'.$idInmueble;
      $data = file_get_contents("$dir/$file");
      $base64 = base64_encode($data);
      $archivo->data = $base64;
      array_push($archivos, $archivo);
    }
    return $archivos;
  }else{
    return false;
  }
}

function saveAlbum($archivos, $idInmueble, $row){
  $url = 'https://sai.propiver.com/SAI/seam/resource/rest/album/save';
  $curl = new Curl();
  $curl->url($url);
  $curl->method('put');
  $album = new stdClass();
  $album->idInmueble = $idInmueble;
  foreach($archivos as $archivo){
    $album->archivos = array($archivo);    
    $curl->data($album);
    try{
      $response = $curl->send();
      $response = json_decode($response);
      jsonPretty($response);
      if(!(isset($response->status) && (strcasecmp($response->status, 'archivos creados.') == 0))){
        $ex = "Failed: $row\nResponse: ".json_encode($response, JSON_PRETTY_PRINT);
        writeOnLog($ex);
      }
    }catch(Exception $ex){
      $ex = "Fila: $row \n$ex";
      echo $ex;
      writeOnLog($ex);
    }
  }
  $curl->close();
}
