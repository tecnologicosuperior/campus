<?php

require_once('../../libs/env/Env.php');

class Conexion {

    protected $db;
    private $Env;

    public function __construct(){

        $this->Env = new Env();

        try {
            $db = new PDO("mysql:host={$this->Env->getVariablesEntorno()['BD_HOST']};dbname={$this->Env->getVariablesEntorno()['BD_DB']};charset=utf8", $this->Env->getVariablesEntorno()['BD_USER'], $this->Env->getVariablesEntorno()['BD_PASS']);
            $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            echo 'Ha surgido un error y no se puede conectar a la base de datos. Detalle: ' . $e->getMessage();
            exit;
        }
    }
}