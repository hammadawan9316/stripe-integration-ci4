<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Response;

class ResponseHandler extends Response
{
    protected $response;
    protected $request;

    public function __construct()
    {
        $this->response = service('response');
        $this->request = service('request');
    }

    public function send($data = null, string $message = '', ?int $status = null, $locale = "en")
    {
        if ($data === null && $status === null) {
            $status = 404;
            $output = null;
        } elseif ($data === null && is_numeric($status)) {
            $output = null;
        } else {
            $status ??= 200;
            $output = $data;
        }
        $responseData = [
            'data' => $output,
            'message' => $message,
            'status' => $status,
        ];
        return $this->response->setJSON($responseData)->setStatusCode($status, $message);
    }
}
