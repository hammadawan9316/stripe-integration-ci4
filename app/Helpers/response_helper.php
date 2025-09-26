<?php

if (!function_exists('sendApiResponse')) {
    function sendApiResponse($data = null, string $message = '', ?int $status = null, $locale = 'en')
    {
        // Load the custom response library
        $responseHandler = new \App\Libraries\ResponseHandler();

        // Use the send method to send a response
        return $responseHandler->send($data, $message, $status, $locale);
    }
}