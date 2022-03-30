<?php
// displayEcho();
require_once __DIR__.'/spreadsheet/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once __DIR__.'/log.php';
require_once __DIR__.'/models/property_model.php';
require_once __DIR__.'/network/curl.php';
require_once __DIR__.'/models/estados_model.php';
require_once __DIR__.'/models/property_type_model.php';
require_once __DIR__.'/models/archivo_model.php';

date_default_timezone_set('America/Asuncion');
set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');

$inputFileName = __DIR__ . '/datos.xlsx';
$imagesPath = __DIR__.'/images';
$csvPath = __DIR__.'/ubicaciones.csv';

$spreadsheet = IOFactory::load($inputFileName);
$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

//write
$sheet = $spreadsheet->getActiveSheet();
$writer = IOFactory::createWriter($spreadsheet, "Xlsx");

$inmueble = new stdClass();

$listMaps = getFromCsv($csvPath);
$remaining = count($sheetData);
echo("\nremaining: $remaining\n");;

foreach($sheetData as $row => $column){
  $status = trim($column['AG']);
  
  if($row > 1 /*&& $status === 'failed'*/){
    echo "row: $row \n";
    $property = new PropertyModel();
    $tipoInmueble = new PropertyTypeModel();
    
    $data = getData($column);
    $property->publicado = true;
    $property->nombreEdificio = $data['nombreEdificio'];
    if(!empty($data['zona'])){
      $property->zona = $data['zona'];
    }
    $property->direccion = $data['direccion'];
    
    $precioAlquiler = 0.0;
    $precioVenta = 0.0;
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
    
    $zona = $data['zona'];
    
    if(!empty($data['ubicacion'])){
      $location = getLocation($data['ubicacion'], $listMaps);
      if($location !== false){
        $property->latitud = $location[0];
        $property->longitud = $location[1];
      }
    }
    
    $tipoInmueble->nombre = strtoupper($data['tipoInmueble']);
    $tipoInmueble->alquiler = $isAlquiler;
    $tipoInmueble->venta = $isVenta;
    $property->tipoInmueble = $tipoInmueble;
    
    if(empty($data['descripcion'])){
      $operacion = ($isVenta && $isAlquiler)?'Venta/Alquiler':($isVenta? 'Venta':'Alquiler');
      $descripcion = $data['tipoInmueble'].' en '.$operacion.' - '.$zona;
    }else{
      $data['descripcion'] = iconv("ISO-8859-1","UTF-8",$data['descripcion']);
      $descripcion = $data['descripcion'];
    } 
    
    $property->descripcion = $descripcion;
    $length = (strlen($descripcion) < 200) ? strlen($descripcion) : 200;
    $property->titulo = substr($descripcion, 0, $length);
    
    $property->precioAlquiler = (float) $precioAlquiler;
    $property->precioVenta = (float) $precioVenta;
    $property->monedaAlquiler = $monedaAlquiler;
    $property->monedaVenta = $monedaVenta;
    
    if(!empty($data['iva']) && strtolower($data['iva']) == 'incluido'){
      $property->iva = true;
    }
    
    if(!empty($data['comisionVenta'])){
      $str = $data['comisionVenta'];
      $porcentajeComisionVenta = parseComision($str);
      if($porcentajeComisionVenta !== false){
        $property->porcentajeComisionVenta = $porcentajeComisionVenta;
        $property->comisionVenta = true;
      } 
    }
    
    $property->propietario = $data['propietario'];
    $property->telefPropietario = $data['telefPropietario'];
    
    $property->dormitoriosSuite = empty($data['dormitoriosSuite']) ? 0 : intval($data['dormitoriosSuite']);
    $property->dormitoriosNormales = empty($data['dormitoriosNormales']) ? 0 : intval($data['dormitoriosNormales']);
    if(existeCondicion($data['condicionInmueble'])){
      $property->condicionInmueble = $data['condicionInmueble'];
    }
    
    if(!empty($data['muebles'])){
      $property->muebles = strcasecmp($data['muebles'], 'no') == 0 ? false: true;
    }
    
    if(!empty($data['baulera'])){
      $property->baulera = strcasecmp($data['baulera'], 'no') == 0 ? false: true;
    }
    
    $property->ascensor = (strcasecmp($data['ascensor'], 'no') == 0 || empty($data['ascensor'])) ? false: true;
    $property->piscina = (strcasecmp($data['piscina'], 'no') == 0 || empty($data['piscina'])) ? false: true;
    $property->parrilla = (strcasecmp($data['parrilla'], 'no') == 0 || empty($data['parrilla'])) ? false: true;
    $property->gimnasio = (strcasecmp($data['gimnasio'], 'no') == 0  || empty($data['gimnasio'])) ? false: true;
    $property->petFriendly = (strcasecmp($data['petFriendly'], 'no') == 0  || empty($data['petFriendly'])) ? false: true;
    $property->baulera = (strcasecmp($data['baulera'], 'no') == 0  || empty($data['baulera'])) ? false: true;
    $property->cartel = (strcasecmp($data['cartel'], 'no')) == 0  || empty($data['cartel']) ? false: true;
    
    if(!empty($data['fechaIngreso'])){
      $data['fechaIngreso'] = str_replace('/','-',$data['fechaIngreso']);
      $fechaIngreso = date('Y-m-d', strtotime($data['fechaIngreso']));
    }else{
      $fechaIngreso = date('Y-m-d', time());
    }
    
    $property->fechaIngreso = $fechaIngreso;
    
    $property->cantDpto = empty($data['cantDpto'])?0:intval($data['cantDpto']);
    $property->descrInmueble = $data['descrInmueble'];
    
    $estado = new Estado();
    $estado->estado = 'DISPONIBLE';
    $estado->fechaHora = $fechaIngreso;
    $property->estados = array($estado);
    
    $inmueble->inmueble = $property;
    // saveProperty($inmueble, $imagesPath, $data['fotos']);
    jsonPretty($inmueble);
  }
  $remaining--;
  echo("\n\nremaining: $remaining\n");
}


function saveProperty($data, $imagesPath, $linkDrive){
  global $row;
  $url = 'https://sai.propiver.com/SAI/seam/resource/rest/inmuebles/save';
  $curl = new Curl();
  $curl->url($url);
  $curl->method('put');
  $curl->data($data);
  try{
    $response = $curl->send();
    $response = json_decode($response);
    echo json_encode($response);
    if(isset($response->id)){
      writeOnExcel('AG', $row, 'ok');
      if(!empty($linkDrive)){
        $archivos = assembleArchivos($linkDrive, $imagesPath, $response->id);
        if (!($archivos ===false)){
          saveAlbum($archivos, $response->id);
        }else{
          writeOnExcel('AH', $row, 'sin fotos');
        }
      }
    }else{
      writeOnExcel('AG', $row, 'failed');
      $ex = "Failed: $row\nResponse: ".json_encode($response, JSON_PRETTY_PRINT);
      writeOnLog($ex);
    }
  }catch(Exception $ex){
    $ex = "Fila: $row \n$ex";
    echo($ex);
    writeOnLog($ex);
    writeOnExcel('AG', $row, 'failed');
  }
  $curl->close();
}

function displayEcho(){
  ob_implicit_flush(true);
  ob_end_flush();
}

function jsonPretty($data){
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
  // echo 'Json: '.json_last_error_msg();
  echo "<pre>$json</pre>";
  // echo "$json";
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
    'tipoInmueble' => trim($c['A']) == '-'? '' : trim($c['A']),
    'publicado' => trim($c['B']) == '-'? '' : trim($c['B']),
    'nombreEdificio' => trim($c['C']) == '-'? '' : trim($c['C']),
    'disponibilidad' => trim($c['D']) == '-'? '' : trim($c['D']),
    'ciudad' => trim($c['E']) == '-'? '' : trim($c['E']),
    'zona' => trim($c['F']) == '-'? '' : trim($c['F']),
    'direccion' => trim($c['G']) == '-'? '' : trim($c['G']),
    'ubicacion' => trim($c['H']) == '-'? '' : trim($c['H']),
    'fotos' => trim($c['I']) == '-'? '' : trim($c['I']),
    'descripcion' => trim($c['J']) == '-'? '' : trim($c['J']),
    'precioAlquilerSm' => trim($c['K']) == '-'? '' : trim($c['K']),
    'precioAlquilerCm' => trim($c['L']) == '-'? '' : trim($c['L']),
    'precioVentaSm' => trim($c['M']) == '-'? '' : trim($c['M']),
    'precioVentaCm' => trim($c['N']) == '-'? '' : trim($c['N']),
    'iva' => trim($c['O']) == '-'? '' : trim($c['O']),
    'comisionVenta' => trim($c['P']) == '-'? '' : trim($c['P']),
    'propietario' => trim($c['Q']) == '-'? '' : trim($c['Q']),
    'telefPropietario' => trim($c['R']) == '-'? '' : trim($c['R']),
    'dormitoriosSuite' => trim($c['S']) == '-'? '' : trim($c['S']),
    'dormitoriosNormales' => trim($c['T']) == '-'? '' : trim($c['T']),
    'condicionInmueble' => trim($c['U']) == '-'? '' : trim($c['U']),
    'muebles' => trim($c['V']) == '-'? '' : trim($c['V']),
    'baulera' => trim($c['W']) == '-'? '' : trim($c['W']),
    'ascensor' => trim($c['X']) == '-'? '' : trim($c['X']),
    'piscina' => trim($c['Y']) == '-'? '' : trim($c['Y']),
    'parrilla' => trim($c['Z']) == '-'? '' : trim($c['Z']),
    'gimnasio' => trim($c['AA']) == '-'? '' : trim($c['AA']),
    'petFriendly' => trim($c['AB']) == '-'? '' : trim($c['AB']),
    'cartel' => trim($c['AC']) == '-'? '' : trim($c['AC']),
    'fechaIngreso' => trim($c['AD']) == '-'? '' : trim($c['AD']),
    'cantDpto' => trim($c['AE']) == '-'? '' : trim($c['AE']),
    'descrInmueble' => trim($c['AF']) == '-'? '' : trim($c['AF']),
    
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
    return empty($archivos) ? false: $archivos;
  }else{
    return false;
  }
}

function saveAlbum($archivos, $idInmueble){
  global $row;
  $url = 'https://sai.propiver.com/SAI/seam/resource/rest/album/save';
  $curl = new Curl();
  $curl->url($url);
  $curl->method('put');
  $album = new stdClass();
  $album->idInmueble = $idInmueble;
  $failed = 0;
  foreach($archivos as $archivo){
    $album->archivos = array($archivo);    
    $curl->data($album);
    try{
      $response = $curl->send();
      $response = json_decode($response);
      echo  ' '.json_encode($response);
      if(!(isset($response->status) && (strcasecmp($response->status, 'archivos creados.') == 0))){
        $ex = "Failed: $row\nResponse: ".json_encode($response, JSON_PRETTY_PRINT);
        writeOnLog($ex);
        $failed++;
      }
    }catch(Exception $ex){
      $ex = "Fila: $row \n$ex";
      echo $ex;
      writeOnLog($ex);
      $failed++;
    }
  }
  writeOnExcel('AH', $row, "ok. failed: $failed");
  $curl->close();
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

function parseComision($str){
  $fmt = new NumberFormatter( 'es_PY', NumberFormatter::DECIMAL );
  $str = number_format(floatval($str), 2, ',','.');
  $valor = $fmt->parse($str);
  return $valor;
}

function existeCondicion($str){
  $str = strtolower($str);
  $condiciones = array('malo','regular','bueno','muy bueno', 'excelente', 'a estrenar', 'en pozo');
  if(in_array($str, $condiciones)){
    return true;
  }else{
    return false;
  }
}

function writeOnExcel($c, $r, $msg){
  global $row;
  try{
    global $sheet, $writer, $inputFileName;
    $c = strtoupper($c);
    $sheet->setCellValue("$c$r", $msg);
    $writer->save($inputFileName);  
  }catch(Exception $ex){
    $ex = "Fila: $row \n$ex";
    echo($ex);
    writeOnLog($ex);
  }
  
}

function errorHandler($errno, $errstr, $errfile, $errline){
  global $row;
  echo "error: row excel: $row, line index: $errline\n";
  $error = "Custom error:\n[$errno] $errstr\nError on line $errline in $errfile\nRow: $row";
  writeOnLog($error);
}
function exceptionHandler($ex){
  global $row;
  $ex = "Row: $row\n$ex";
  echo "exception\n";
  writeOnLog($ex);
}