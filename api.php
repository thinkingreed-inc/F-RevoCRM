<?php
$basicAuth = "frevo:Ly2mX#bExHdZ";
$URL = "http://avenue.aegif.jp:11252/alfresco/service/frevo/pot_documents?acc=$acc&pot=$pot";
$URL = "http://avenue.aegif.jp:11252/alfresco/service/frevo/pot_documents?acc=ACC8&pot=POT86";
$opts = [
  'http' => [
    'method' => 'GET',
    'header' => 'Authorization: Basic ' . base64_encode($basicAuth),
  ]
];
$data = file_get_contents($URL, false, stream_context_create($opts));
echo(print_r($data, true));
