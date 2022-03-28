<?php

include __DIR__.'/models/archivo_model.php';
$imagesPath = __DIR__.'/images';
include __DIR__.'/network/curl.php';
require __DIR__.'/log.php';
$csvPath = __DIR__.'/ubicaciones-test.csv';

// displayEcho();
// $link1 = 'https://drive.google.com/drive/folders/1v7MA25xW1gwBp8rHIVLuGRCnFTnmH5aV?usp=sharing';
// $link2 = 'https://drive.google.com/file/d/1qck88s3EJRkjl6W-7ezMe_pC-VyLj0iy/view?usp=sharing'; 
$link3 = 'https://goo.gl/maps/tgUuQCXvqjwctkzMA';

// $idInmueble = 623;
// $archivos = assembleArchivos($link1, $imagesPath, $idInmueble);
// var_dump($archivos);
// saveAlbum($archivos, $idInmueble);
// $id = null;
// $object = new stdClass();
// $object->prop = 'Property';

// writeOnLog(json_encode($object, JSON_PRETTY_PRINT));


// $list = getFromCsv($csvPath);


// getLocation($link3, $list);

// var_dump($list);

$precio = 'USD 300.000,5 bajo 35.000 hoy 18/09/21';
$p = parseMoney($precio);
var_dump($p);

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
    return empty($archivos) ? false: $archivos;
  }else{
    return null;
  }
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

function saveAlbum($archivos, $idInmueble){
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
      jsonPretty(json_decode($response));
    }catch(Exception $ex){
      echo $ex;
      writeOnLog($ex);
    }
  }
  $curl->close();
}

function jsonPretty($data){
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES);
  echo "<pre>$json</pre>";
}

function displayEcho(){
  ob_implicit_flush(true);
  ob_end_flush();
}

function getLocation($link, $list){ 
  $result = preg_grep( '~^'.$link.'~', $list);
  
  if(!empty($result)){
    $location = explode(';', reset($result));
    $location = explode(',', $location[1]);
    $location[0] = floatval($location[0]);
    $location[1] = floatval($location[1]);
    return($location);
  }else{
    return false;
  }
}

function getFromCsv($csvPath){
  $csv = fopen($csvPath, "r");
  $links = fgetcsv($csv, 0, '*');
  fclose($csv);
  return $links;
}

function parseMoney($str){
  $fmt = new NumberFormatter( 'es_PY', NumberFormatter::DECIMAL );
  $str = trim($str);
  $simbolo = trim(substr($str, 0 ,strpos($str, ' ')));
  if(strcasecmp($simbolo, 'USD') == 0){
    $simbolo = '$';
  }
  $valor = substr($str, strpos($str, ' ')+1, strlen($str));
  echo $valor."\n";
  return array($fmt->parse($valor), $simbolo);
}