<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home.index');
    }

    /**
     * Show the application linked social accounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function socialAccounts()
    {
        return view('home.socialAccounts', ['socialAccounts' => auth()->user()->accounts()]);
    }
}
