<?php

require_once('../modelo/Campus.php');

$Campus = new Campus();

$json = file_get_contents("php://input");

$request = json_decode($json, true);

echo $Campus->getNotasCompletasEstudiante($request);