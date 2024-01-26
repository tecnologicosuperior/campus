<?php

require_once('../../libs/autoload.php');
require_once('../../libs/env/Env.php'); 
require_once('../../configuracion/Conexion.php');

date_default_timezone_set('America/Bogota');

class Media extends Conexion {

    private $JWToken            = null;
    private $Token              = null;
    private $Bucket             = null;

    public function __construct() {

        $this->db = parent::__construct();

        $this->JWToken  = new JWToken();

        $this->Bucket   = new Bucket();        

        $this->Token = json_decode($this->JWToken->verify());
    }

    public function getMediaFile($mediaId) {

        try {


            $mediaURL = $this->Bucket->getDocumentoBucket('media/', $mediaId);

            return json_encode(array('status' => 'success', 'url' => $mediaURL));

        } catch (Exception $e) {
            
            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }
}