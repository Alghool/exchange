<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    protected $session = null;

	protected $twig = null;


    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // E.g.: $this->session = \Config\Services::session();
	    //todo:check for user logged in ant type
	    $this->session = \Config\Services::session();
	    $this->twig = new \Daycry\Twig\Twig();

	    $user = $this->session->get('user');
	    if($user)  $this->twig->addGlobal('user', $user);
		$this->getCorrectNavbar($user);

    }

    protected function getCorrectNavbar($user){
	    if ($user){
		    if ($user['type'] == 'lister'){
			    $this->twig->addGlobal('navbar', 'lister');
		    }
		    elseif ($user['type'] == 'investor'){
			    $this->twig->addGlobal('navbar', 'investor');
		    }
		    elseif ($user['type'] == 'admin'){
			    $this->twig->addGlobal('navbar', 'admin');
		    }
	    }
	    else{
		    $this->twig->addGlobal('navbar', 'logout');
	    }
    }

}
