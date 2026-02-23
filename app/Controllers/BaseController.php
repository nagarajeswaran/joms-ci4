<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'form', 'text'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }
    
    // NO AUTHENTICATION - Disabled for now
    protected function isLoggedIn(): bool
    {
        return true; // Always return true
    }
    
    protected function getUser()
    {
        return ['id' => 1, 'username' => 'admin'];
    }
    
    protected function requireAuth()
    {
        // Do nothing - auth disabled
    }
}
