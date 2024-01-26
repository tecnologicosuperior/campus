<?php

require_once('../../../vendor/autoload.php'); 
require_once('../../libs/env/Env.php'); 

use Aws\S3\S3Client;

use Psr\Http\Message\UriInterface;

class Bucket {

	private $Bucket;
	private $Env;

	public function __construct(){
		$this->Env = new Env();

	    $Options =
            [
                'version' => 'latest',
                'region'  => 'us-east-1',
                'credentials' =>
                [
                    'key' => $this->Env->getVariablesEntorno()['AWS_KEY'],
                    'secret' => $this->Env->getVariablesEntorno()['AWS_SECRET']
                ]
            ];
        $this->Bucket = new S3Client($Options);
	}

	public function registrarDocumentoBucket($Folder, $Nombre, $Source, $Type) {

		$this->Bucket->putObject([
            'Bucket' => $this->Env->getVariablesEntorno()['AWS_BUCKET'],
            'Key' => $Folder . $Nombre,
            'SourceFile' => $Source,
            'ContentType' => $Type, 
            'ContentDisposition' => 'inline; filename=' . $Nombre,
        ]);
	}

	public function eliminarDocumentoBucket($Folder, $Nombre) {

		$this->Bucket->deleteObject(
		[
			'Bucket' => $this->Env->getVariablesEntorno()['AWS_BUCKET'],
			'Key' => $Folder.$Nombre
		]);
	}

	public function getDocumentoBucket($Folder, $Nombre): UriInterface {
        
		$URLComprobante = $this->Bucket->getCommand('GetObject', ['Bucket' => $this->Env->getVariablesEntorno()['AWS_BUCKET'], 'Key' => $Folder.$Nombre]);
		$Request = $this->Bucket->createPresignedRequest($URLComprobante, '+60 minutes');
		return $Request->getUri();
	}

	public function getTipoDocumento($tipo, $nombre) {

		if ($tipo == 'application/pdf') {

			$extension = '.pdf';	
		} else if ($tipo == 'image/png') {

			$extension = '.png';
		} else if ($tipo == 'image/jpeg') {

			$extension = '.jpg';
        } else if ($tipo == 'video/mp4') {

			$extension = '.mp4';
		} else if ($tipo == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {

			$extension = '.docx';
		} else if ($tipo == 'application/vnd.ms-excel' || $tipo == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            
			$extension = '.xls';
		} else {
			return json_encode(array('status' => 'error', 'message' => 'Tipo de archivo no permitido: Solo se permiten: pdfs, jpgs, pngs, docx, mp4 y xls'));
		}
		return $nombre.$extension;
	}
}