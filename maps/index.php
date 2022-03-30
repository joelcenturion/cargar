<?php

$locationsPath = __DIR__.'/locations.txt';
$failedPath = __DIR__.'/failed.txt';
$csvPath = __DIR__.'/links.csv';

set_exception_handler("writeOnLog");

// displayEcho();

$linkList = getLinksFromCsv($csvPath);
$failed = 0;
$success = 0;
$total = count($linkList);

echo "Remaining: $total - ";
echo "Success: $success - ";
echo "Failed: $failed\n";

foreach($linkList as $link){
  $total--;
  
  $location = getLocation($link);
  
  if($location !== false){
    $text = "$link;$location";
    writeOnFile($locationsPath, $text); 
    echo "$text\n";
    $success++;
  }else{
    $url = getUrl($link);
    $location = getLocation($url);
    if($location !== false){
      $text = "$link;$location";
      writeOnFile($locationsPath, $text); 
      echo "$text\n";
      $success++;
    }else{
      writeOnFile($failedPath,"$link -> $url");
      echo "Failed: $link\n";
      $failed++;
    }

  }
  echo "    [Remaining: $total - ";
  echo "Success: $success - ";
  echo "Failed: $failed]\n";
}
echo "Success: $success\n";
echo "Failed: $failed\n";
/*************************************/

function getRedirectUrl ($url) {
  stream_context_set_default(array(
      'https' => array(
          'method' => 'HEAD'
      )
  ));
  $headers = get_headers($url, 1);
    
  if ($headers !== false && isset($headers['Location'])) {
    return is_array($headers['Location']) ? array_pop($headers['Location']) : $headers['Location'];
  }
  return false;
}

function getUrl ($url){

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

  $html = curl_exec($ch);

  $redirectedUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

  curl_close($ch);

  return $redirectedUrl;
}

function displayEcho(){
  ob_implicit_flush(true);
  ob_end_flush();
}


function getLocation($url){
  $result = preg_match('/(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $match);
  if($result == false){
    return false;
  }else{
    return $match[0];
  }
  
}

function writeOnFile($filePath, $text){
  file_put_contents($filePath, "$text\n", FILE_APPEND);
}

function writeOnLog($exception){
  $logPath = __DIR__.'/log.log';
  $e = $exception;
  $e = "\n*************\n$e\n*************\n";
  file_put_contents($logPath, "$e", FILE_APPEND);
  echo "\nException\n";
}

function getLinksFromCsv($csvPath){
  
  $csv = fopen($csvPath, "r");
  $links = fgetcsv($csv, 0, ';');
  fclose($csv);
  return $links;
    
}
