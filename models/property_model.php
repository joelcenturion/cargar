<?php

class PropertyModel {
  public $idinmueble;
  public $titulo;
  public $fechaIngreso;
  public $direccion;
  public $latitud;
  public $longitud;
  public $descripcion;
  public $precioAlquiler;
  public $precioVenta;
  public $comisionVenta=false;
  public $porcentajeComisionVenta=0.0;
  public $tipoInmueble;
  public $monedaVenta;
  public $monedaAlquiler;
  public $publicado;
  public $nombreEdificio;
  public $estados;
  public $zona;
  public $iva;
  public $propietario;
  public $telefPropietario;

    
  public $areasComunes = 0;
  public $cantPisos = 0;
  public $cantDpto=0;
  public $cocheras=0;
  public $bauleras=0;
  public $dormitoriosSuite=0;
  public $dormitoriosNormales=0;
  public $dormitoriosPlantaBaja = 0;
  
}