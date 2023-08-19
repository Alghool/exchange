<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;


class Attachment extends BaseController
{
	public function getkoko($folder, $fileName)
	{
		$path = WRITEPATH.'uploads/'.$folder .'/' .$fileName;
		$fileObject = new \CodeIgniter\Files\File($path);

		if(!file_exists($path))
			show_404();

		// choose the right mime type
		$mimeType =  $fileObject->getMimeType();
		$file = file_get_contents($path);

		$this->response
			->setStatusCode(200)
			->setContentType($mimeType)
			->setBody($file)
			->send();

	}


}