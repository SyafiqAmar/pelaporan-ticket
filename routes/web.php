<?php

use Illuminate\Support\Facades\Route;
use App\Models\Faq;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/faq', function () {
    return view('faq', [
        'faqs' => Faq::all(),
    ]);
});
