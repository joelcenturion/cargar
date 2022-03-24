<?php

$url = "https://reqbin.com/echo/put/json";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_PUT, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Content-Type: application/json",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = <<<DATA
{
  "Id": 12345,
  "Customer": "John Smith",
  "Quantity": 1,
  "Price": 10.00
}
DATA;

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);



$resp = curl_exec($curl);
curl_close($curl);
var_dump($resp);

?>

