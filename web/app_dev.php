<?php
/**
 * Default entry point for microframework based app
 */

use Symfony\Component\HttpFoundation\Request;

date_default_timezone_set('Europe/Berlin');

// require Composer's autoloader
require __DIR__.'/../app/autoload.php';

if (getenv('SYMFONY_ENV') != 'dev')
{
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$kernel = new AppKernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
