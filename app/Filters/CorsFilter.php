<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $origin = $request->getHeaderLine('Origin');

        // Allowed origins from .env
        $allowedOrigins = getenv('cors.allowedOrigins')
            ? array_map('trim', explode(',', getenv('cors.allowedOrigins')))
            : ['*'];

        // Pick allowed origin
        $allowOrigin = '*';
        if ($origin && (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins))) {
            $allowOrigin = $origin;
        }

        header("Access-Control-Allow-Origin: $allowOrigin");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 3600");

        // Handle preflight OPTIONS
        if ($request->getMethod() === 'options') {
            return service('response')
                ->setStatusCode(200)
                ->setBody('OK');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No need here since headers already sent in before()
        return $response;
    }
}
