<?php

require_once('../modelo/Campus.php');

$Campus = new Campus();

echo $Campus->getEstudiantesAprobados();