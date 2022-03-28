<?php

class Curl{
  private $url;
  private $method = 'PUT';
  private $data = [];
  private $handler = null;
  public $response = '';

  function __construct(){
    $this->handler = curl_init();
  }
  
  public function url ($url){
    $this->url = $url;
  }
  
  public function handler($handler){
    $this->handler = $handler;
  }  
  
  public function method($method = 'put'){
    $this->method = $method;
  }
  
  public function data($data = []){
    $this->data = $data;
  }
  
  public function send(){
    $headers = array(
      "Content-Type: application/json",
      "Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJTQUkiLCJpYXQiOjE2MjI2MzU5MzQsInN1YiI6ImFkbWluIiwiaXNzIjoiMzUzMjcwMDY1MzEyNjAiLCJleHAiOjE2MjI2NTc1MzR9.TGGAVEWBWwuVD3NgRux3zb9jOn4Iq2Aa5fuP2OllIbk"
    );
    try{
      
      if($this->handler == null){
        $this->handler = curl_init();
      }
      
      switch(strtolower($this->method)){
        case 'put':
          curl_setopt_array ( $this->handler , [
              CURLOPT_URL => $this->url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_CUSTOMREQUEST => 'PUT',
              CURLOPT_POSTFIELDS => json_encode($this->data, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
              CURLOPT_HTTPHEADER => $headers
          ] );
        break;
        default:
          curl_setopt_array ( $this->handler , [
              CURLOPT_URL => $this->url,
              CURLOPT_RETURNTRANSFER => true,
          ]);
        break;
      }
     
      $this->response = curl_exec($this->handler);
      return $this->response;
      
    }catch(Exception $ex){
      $ex = ($this->url)."\n".($this->data)."\n".$ex;
      throw $ex;
    }
    
  }
  
  public function close(){
    curl_close($this->handler);
    $this->handler = null;
  }
  
  
}