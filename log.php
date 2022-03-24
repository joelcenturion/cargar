<?php

function writeOnLog($exception){
  $logPath = __DIR__.'/log.log';
  $e = $exception;
  $e = "\n*************\n$e\n*************\n";
  file_put_contents($logPath, "$e", FILE_APPEND);
  echo "\nException\n";
}