<?php
namespace App\Http\Controllers;

class LoginController extends Controller {


    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function index()
    {
        return view('login');
    }

}
