<?php

namespace App\Controllers;

class CardsPayments extends BaseController
{
    public function index(): string
    {
        return view('card_payments/index');
    }
}
