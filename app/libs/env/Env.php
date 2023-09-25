<?php

require_once('../../../vendor/autoload.php'); 

use Symfony\Component\Dotenv\Dotenv;

class Env {

    private $Env = null;
    private $VariablesEntorno = null;

    public function __construct(){

        $this->Env = new Dotenv();
        $this->Env->load('../../../../../envs/.env-campus');

        $this->VariablesEntorno = $_ENV;
    }

    public function getVariablesEntorno(){
        return $this->VariablesEntorno;
    }
}