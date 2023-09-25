<?php

require_once('jwt/JWToken.php'); 

class AutloadLibs {
    
    private $JWToken    = null;

    public function __construct() {
        
        $this->JWToken      = new JWToken();
    }
}
