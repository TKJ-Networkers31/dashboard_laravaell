<?php

namespace App\Filament\Pages\Auth;

// use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected string $view = 'filament.admin.pages.auth.login';
}
