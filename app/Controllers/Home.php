<?php

namespace App\Controllers;
class Home extends BaseController
{
    public function index()
    {
        header("Access-Control-Allow-Origin: http:localhost:8080");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        return view('welcome_message');
    }
}
