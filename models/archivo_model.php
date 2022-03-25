<?php

class Archivo{
  
  public $nombre;
  public $imagen = true;
  public $principal = false;
  public $mimeType;
  public $pathArchivo;
  public $data;
}

/*
{
  "archivos": [
    instancia de Archivo
  ]
  "idInmueble": $idinmueble
  
}
$album = new stdClass();
$album->archivos = array(instancia de Archivo);
$album->idInmueble = $idinmueble;

*/