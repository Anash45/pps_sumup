<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('build-pdf', 'BuildPdf::index');
$routes->get('download/(:any)', 'BuildPdf::download/$1');
