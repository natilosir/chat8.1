<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ AuthController::class, 'login' ]);
Route::post('/register', [ AuthController::class, 'register' ]);

Route::group([ 'middleware' => 'auth:sanctum' ], function () {
    Route::post('/check', [ AuthController::class, 'check' ]);
    Route::post('logout', [ AuthController::class, 'logout' ]);
    Route::post('refresh', [ AuthController::class, 'refresh' ]);
    Route::post('me', [ AuthController::class, 'me' ]);

    Route::post('/CreateChat', [ ChatController::class, 'Create' ]);
    Route::post('/GetChatsData', [ ChatController::class, 'GetChats' ]);
    Route::post('/AllChats', [ ChatController::class, 'show' ]);
    Route::post('/send', [ ChatController::class, 'send' ]);
    Route::post('/edit', [ ChatController::class, 'edit' ]);
    Route::post('/load', [ ChatController::class, 'load' ]);
});
