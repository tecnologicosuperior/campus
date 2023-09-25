<?php

require_once('../../../vendor/autoload.php'); 
require_once('../../libs/env/Env.php'); 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWToken {

    private $JWT                = null;
    private $Env                = null;
    private $Key                = '';
    private $VariablesEntorno   = null;

    private $AllowedOrigins = [
        'https://tecnologicosuperior.edu.co/',
        'http://tecnologicosuperior.edu.co/',
        'http://localhost/'
    ];

    public function __construct() {

        $this->JWT = new JWT();

        $this->Env = new Env();

        $this->VariablesEntorno = $this->Env->getVariablesEntorno();

        $this->Key = $this->VariablesEntorno['JWT_KEY'];

        $this->verificarOrigen();
    }
    
    /**
     * Verifica que el origen de la solicitud esté permitido.
     * 
     * @return void
     */
    public function verificarOrigen() {

        /*if (isset($_SERVER['HTTP_ORIGIN'])) {

            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

            if (in_array($origin, $this->AllowedOrigins)) {

                header("Access-Control-Allow-Origin: $origin");
                header('Access-Control-Allow-Methods: GET, POST');
                header('Access-Control-Allow-Headers: Content-Type');
            } else {
                http_response_code(403);
                echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
                exit();
            }
        } else {
            http_response_code(403);
            echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
            exit();
        }*/
    }

    /**
     * Codifica los datos proporcionados en un token JWT.
     *
     * @param mixed $data Los datos a codificar.
     * @return string El token JWT codificado.
     */
    public function encode($data) {

        $now = strtotime('now');
        $payload = [
            'exp' => $now + 3600,
            'data' => $data
        ];
        return $this->JWT->encode($payload, $this->Key, 'HS256');
    }

    /**
     * Decodifica un token JWT y devuelve los datos contenidos en él.
     *
     * @param string $jwt El token JWT a decodificar.
     * @return object Los datos decodificados del token.
     */
    public function decode($jwt) {
        $jwt_decode =  $this->JWT->decode($jwt, new Key($this->Key, 'HS256'));
        return $jwt_decode;
    }

    /**
     * Obtiene los datos contenidos en el token actual.
     *
     * @return object|false Los datos contenidos en el token o false si el token es inválido.
     */
    public function getDataToken() {
        $jwt = $this->getJWTHeader();
        try {
            return json_decode($this->decode($jwt)->data);
        } catch (Exception $e) {
            http_response_code(401);
            return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
        }
    }

    /**
     * Verifica la validez del token actual.
     *
     * @return string El estado de verificación del token ('success' o 'error').
     */
    public function verify() {

        $jwt = $this->getJWTHeader();

        if($jwt == null){
            return json_encode(array('status' => 'error', 'message' => 'Token Experied'));
        }

        try {
            $this->decode($jwt);
            return json_encode(array('status' => 'success', 'message' => 'Token Valid'));
        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
        }
    }

    /**
     * Obtiene el encabezado del token JWT de la solicitud actual.
     *
     * @return string|null El encabezado del token JWT o null si no se encuentra.
     */
    public function getJWTHeader(){
        $headers = apache_request_headers();
        $jwt = null;
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            $matches = array();
            preg_match('/Bearer (.+)/', $auth_header, $matches);
            if (count($matches) > 1) {
                $jwt = $matches[1];
            }
        }
        if(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])){
            if (preg_match('/Bearer\s(\S+)/', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
                $jwt = $matches[1];
            }
        }
        return $jwt;
    }

    public function verifyAuth() {

        $token = $this->getJWTHeader();

        if ($token === $this->VariablesEntorno['TOKEN_CAMPUS_TECNOLOGICO']) {
            return true;
        }

        return true;
    }
}