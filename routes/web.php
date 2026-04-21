<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\userRegister;

Route::get('/', function () {
    return redirect('/admin');
});
