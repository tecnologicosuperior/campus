<?php

require_once('jwt/JWToken.php'); 
require_once('bucket/Bucket.php'); 

class AutloadLibs {
    
    private $JWToken    = null;
    private $Bucket     = null;

    public function __construct() {
        
        $this->JWToken      = new JWToken();
        $this->Bucket       = new Bucket();
    }
}
