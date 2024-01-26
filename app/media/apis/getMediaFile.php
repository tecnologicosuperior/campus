<?php

require_once('../modelo/Media.php');

$Media = new Media();

echo $Media->getMediaFile($_GET['mediaId']);