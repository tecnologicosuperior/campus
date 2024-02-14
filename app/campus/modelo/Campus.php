<?php

require_once('../../libs/autoload.php');
require_once('../../libs/env/Env.php'); 
require_once('../../configuracion/Conexion.php');

date_default_timezone_set('America/Bogota');

class Campus extends Conexion {

    private $JWToken            = null;
    private $Token              = null;

    public function __construct() {

        $this->db = parent::__construct();

        $this->JWToken  = new JWToken();

        $this->Token = json_decode($this->JWToken->verify());
    }

    public function getEstudiantesSinIngresar() {

        try {

            if ($this->Token->status === 'success') {

                $statement = $this->db->prepare("SELECT DISTINCT u.email AS CORREO, c.fullname AS DIPLOMADO, c.id AS ID_DIPLOMADO, cc.name AS CENTRO, DATE_FORMAT(FROM_UNIXTIME(c.startdate),'%Y-%m-%d') AS FECHA_INICIO_DIPLOMADO, u.firstname AS NOMBRES, u.lastname AS APELLIDOS, u.username AS DOCUMENTO, 
                    (SELECT DATE_FORMAT(FROM_UNIXTIME(timeaccess),'%Y-%m-%d') FROM mdl_user_lastaccess WHERE userid = u.id and courseid = c.id) AS ULTIMO_INGRESO_CURSO 
                    FROM mdl_user_enrolments AS ue 
                    INNER JOIN mdl_enrol AS e on e.id = ue.enrolid 
                    INNER JOIN mdl_course AS c ON c.id = e.courseid 
                    INNER JOIN mdl_course_categories cc ON cc.id = c.category 
                    INNER JOIN mdl_user AS u ON u.id = ue.userid 
                    LEFT JOIN mdl_user_lastaccess AS ul ON ul.userid = u.id 
                    INNER JOIN mdl_role r ON r.id = e.roleid 
                    WHERE ul.timeaccess IS NULL 
                    AND r.id = 5 
                    AND c.visible = 1 
                    AND u.email NOT LIKE '%tecnologicosuperior.edu.co%' 
                    AND NOT EXISTS (
                        SELECT 1 FROM seguimiento_correos 
                        WHERE CORREO = u.email 
                        AND TIPO_CORREO = 'Ingreso'
                    )
                    AND cc.name LIKE '%ACTIVOS%'
                    GROUP BY u.email, c.fullname
                ");

                $statement->execute();

                $estudiantes = $statement->fetchAll(PDO::FETCH_ASSOC);

                if (!is_null($estudiantes)) {

                    foreach ($estudiantes as $estudiante) {

                        //$this->registrarSeguimientoCorreo($estudiante['NOMBRES'], $estudiante['APELLIDOS'], $estudiante['DOCUMENTO'], $estudiante['CORREO'], $estudiante['DIPLOMADO'], $estudiante['CENTRO'], 'Ingreso');
                    }
                }
    
                return json_encode(array('status' => 'success', 'estudiantes' => $estudiantes));
            } else {
                return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
            }
        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    public function getEstudiantesSinParticipar() {

        try {

            if ($this->Token->status === 'success') {

                $statement = $this->db->prepare("SELECT DISTINCT u.email AS CORREO, c.fullname AS DIPLOMADO, c.id AS ID_DIPLOMADO, cc.name AS CENTRO, DATE_FORMAT(FROM_UNIXTIME(c.startdate),'%Y-%m-%d') AS FECHA_INICIO_DIPLOMADO, u.firstname AS NOMBRES, u.lastname AS APELLIDOS, u.username AS DOCUMENTO, 
                    (SELECT DATE_FORMAT(FROM_UNIXTIME(timeaccess),'%Y-%m-%d') FROM mdl_user_lastaccess WHERE userid = u.id and courseid = c.id) AS ULTIMO_INGRESO_CURSO 
                    FROM mdl_user_enrolments AS ue
                    INNER JOIN mdl_enrol AS e on e.id = ue.enrolid 
                    INNER JOIN mdl_course AS c ON c.id = e.courseid 
                    INNER JOIN mdl_course_categories cc ON cc.id = c.category 
                    INNER JOIN mdl_user AS u ON u.id = ue.userid 
                    LEFT JOIN mdl_user_lastaccess AS ul ON ul.userid = u.id 
                    INNER JOIN mdl_role r ON r.id = e.roleid 
                    WHERE ul.timeaccess IS NOT NULL 
                    AND r.id = 5 
                    AND c.visible = 1 
                    AND u.email NOT LIKE '%tecnologicosuperior.edu.co%' 
                    AND NOT EXISTS (
                        SELECT 1 FROM seguimiento_correos 
                        WHERE CORREO = u.email 
                        AND (TIPO_CORREO = 'Participacion' OR TIPO_CORREO = 'Aprobacion') 
                    )
                    AND cc.name NOT LIKE '%BASE%'
                    GROUP BY u.email, c.fullname
                ");

                $statement->execute();

                $results = $statement->fetchAll(PDO::FETCH_ASSOC);

                $estudiantes = array();

                if (!is_null($results)) {

                    foreach ($results as $result) {

                        $promedio = $this->getPromedioEstudianteDiplomado($result['DOCUMENTO'], $result['CENTRO'], $result['DIPLOMADO']);

                        if ($promedio == 0) {

                            array_push($estudiantes, $result);

                            $this->registrarSeguimientoCorreo($result['NOMBRES'], $result['APELLIDOS'], $result['DOCUMENTO'], $result['CORREO'], $result['DIPLOMADO'], $result['CENTRO'], 'Participacion');
                        }
                    }
                }
    
                return json_encode(array('status' => 'success', 'estudiantes' => $estudiantes));
            } else {
                return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
            }
        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    public function getEstudiantesAprobados() {

        try {

            if ($this->Token->status === 'success') {

                $statement = $this->db->prepare("SELECT u.firstname AS 'NOMBRES', u.lastname AS 'APELLIDOS', u.username AS 'DOCUMENTO', u.email AS 'CORREO', c.fullname AS 'DIPLOMADO', c.id AS ID_DIPLOMADO, cc.name AS 'CENTRO', ROUND( gg.finalgrade, 2) AS 'PROMEDIO' 
                    FROM mdl_course c 
                    INNER JOIN mdl_context AS ctx ON c.id = ctx.instanceid 
                    INNER JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id 
                    INNER JOIN mdl_user AS u ON u.id = ra.userid 
                    INNER JOIN mdl_grade_grades AS gg ON gg.userid = u.id 
                    INNER JOIN mdl_grade_items AS gi ON gi.id = gg.itemid 
                    INNER JOIN mdl_course_categories AS cc ON cc.id = c.category 
                    WHERE gi.courseid = c.id AND gi.itemtype = 'course' 
                    AND c.visible = 1 
                    AND ROUND( gg.finalgrade, 2 ) >= 60 
                    AND NOT EXISTS (
                        SELECT 1 FROM seguimiento_correos 
                        WHERE CORREO = u.email
                        AND DIPLOMADO = c.fullname
                        AND TIPO_CORREO = 'Aprobacion'
                    )
                ");

                $statement->execute();

                $estudiantes = $statement->fetchAll(PDO::FETCH_ASSOC);

                if (!is_null($estudiantes)) {

                    foreach ($estudiantes as $estudiante) {

                        $this->registrarSeguimientoCorreo($estudiante['NOMBRES'], $estudiante['APELLIDOS'], $estudiante['DOCUMENTO'], $estudiante['CORREO'], $estudiante['DIPLOMADO'], $estudiante['CENTRO'], 'Aprobacion');
                    }
                }

                return json_encode(array('status' => 'success', 'estudiantes' => $estudiantes));
            } else {
                return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
            }

        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    public function getPromedioEstudianteDiplomado($documento, $diplomado, $centro) {

        try {

            $statement = $this->db->prepare("SELECT ROUND(SUM( gg.finalgrade ) / 5, 2) AS PROMEDIO 
                FROM mdl_course AS c 
                INNER JOIN mdl_context AS ctx ON c.id = ctx.instanceid 
                INNER JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id 
                INNER JOIN mdl_user AS u ON u.id = ra.userid 
                INNER JOIN mdl_grade_grades AS gg ON gg.userid = u.id 
                INNER JOIN mdl_grade_items AS gi ON gi.id = gg.itemid 
                INNER JOIN mdl_course_categories AS cc ON cc.id = c.category 
                WHERE u.username = :documento 
                AND c.fullname = :diplomado 
                AND cc.name = :centro 
                AND gi.courseid = c.id 
                AND gi.itemname != 'Attendance' 
                AND gi.calculation IS NULL
            ");

            $statement->execute(array(
                ':documento' => $documento,
                ':diplomado' => $diplomado,
                ':centro' => $centro
            ));

            return $statement->fetch(PDO::FETCH_ASSOC)['PROMEDIO'];

        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    public function registrarSeguimientoCorreo($nombres, $apellidos, $documento, $correo, $diplomado, $centro, $tipoCorreo) {

        try {

            $statement = $this->db->prepare("INSERT INTO seguimiento_correos (NOMBRES, APELLIDOS, DOCUMENTO, CORREO, DIPLOMADO, CENTRO, TIPO_CORREO) VALUES (:nombres, :apellidos, :documento, :correo, :diplomado, :centro, :tipoCorreo)");
            $statement->execute(array(
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':documento' => $documento,
                ':correo' => $correo,
                ':diplomado' => $diplomado,
                ':centro' => $centro,
                ':tipoCorreo' => $tipoCorreo
            ));

            return json_encode(array('status' => 'success', 'message' => 'Seguimiento de correo registrado correctamente'));

        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    public function getNotasCompletas(){

        try {

            if ($this->Token->status === 'success') {

                $statement = $this->db->prepare("SELECT u.id AS USUARIO, c.id AS DIPLOMADO, cc.name AS CENTRO, CASE WHEN gi.itemtype = 'course' THEN c.fullname + ' Course Total' ELSE gi.itemname END AS ACTIVIDAD, ROUND(gg.finalgrade, 2) AS NOTA 
                    FROM mdl_course AS c 
                    JOIN mdl_context AS ctx ON c.id = ctx.instanceid 
                    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id 
                    JOIN mdl_user AS u ON u.id = ra.userid 
                    JOIN mdl_grade_grades AS gg ON gg.userid = u.id 
                    JOIN mdl_grade_items AS gi ON gi.id = gg.itemid 
                    JOIN mdl_course_categories as cc ON cc.id = c.category 
                    WHERE gi.courseid = c.id 
                    AND cc.name != 'BASE' 
                    AND c.visible = 1 
                    ORDER BY `Category` ASC
                ");

                $statement->execute();

                return json_encode(array('status' => 'success', 'notas' => $statement->fetchAll(PDO::FETCH_ASSOC)));
            
            } else {
                return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
            }
            
        } catch (Exception $e) {
            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    public function getNotasCompletasEstudiante($request){

        try {

            if ($this->Token->status === 'success') {

                $userId     = $request['userId'];
                $courseId   = $request['courseId'];

                $statement = $this->db->prepare("SELECT u.id AS USUARIO, c.id AS DIPLOMADO, cc.name AS CENTRO, CASE WHEN gi.itemtype = 'course' THEN c.fullname + ' Course Total' ELSE gi.itemname END AS ACTIVIDAD, ROUND(gg.finalgrade, 2) AS NOTA 
                    FROM mdl_course AS c 
                    JOIN mdl_context AS ctx ON c.id = ctx.instanceid 
                    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id 
                    JOIN mdl_user AS u ON u.id = ra.userid 
                    JOIN mdl_grade_grades AS gg ON gg.userid = u.id 
                    JOIN mdl_grade_items AS gi ON gi.id = gg.itemid 
                    JOIN mdl_course_categories as cc ON cc.id = c.category 
                    WHERE gi.courseid = c.id 
                    AND cc.name != 'BASE' 
                    AND c.visible = 1 
                    AND u.id = :userId
                    AND c.id = :courseId
                    ORDER BY `Category` ASC
                ");

                $statement->execute(array(':userId' => $userId, ':courseId' => $courseId));

                return json_encode(array('status' => 'success', 'notas' => $statement->fetchAll(PDO::FETCH_ASSOC)));

            } else {
                return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
            }
            
        } catch (Exception $e) {

            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }

    public function autenticacionCampus() {

        try {

            if ($this->JWToken->verifyAuth()) {

                return json_encode(array('status' => 'success', 'token' => $this->JWToken->encode(null)));
            } else {
                return json_encode(array('status' => 'error', 'message' => 'Token Invalid'));
            }
        } catch (Exception $e) {

            return json_encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }
}