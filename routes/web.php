<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TotemController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\StationController;
use App\Http\Middleware\CheckStationAccess;

// Rotte pubbliche
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Rotte per il totem
Route::prefix('totem')->name('totem.')->group(function () {
    Route::get('/', [TotemController::class, 'index'])->name('index');
    Route::post('/create-ticket', [TotemController::class, 'createTicket'])->name('create-ticket');
});

// Rotte per i monitor
Route::prefix('monitor')->name('monitor.')->group(function () {
    Route::get('/reception', [MonitorController::class, 'receptionMonitor'])->name('reception');
    Route::get('/services', [MonitorController::class, 'servicesMonitor'])->name('services');
    Route::get('/data', [MonitorController::class, 'getMonitorData'])->name('data');
});


// Rotte per l'accesso alle postazioni (senza autenticazione)
Route::prefix('station')->name('station.')->group(function () {
    Route::get('/', [StationController::class, 'index'])->name('index');
    Route::post('/select', [StationController::class, 'select'])->name('select');
    Route::get('/access/{stationId}', [StationController::class, 'access'])->name('access');
    Route::post('/close', [StationController::class, 'close'])->name('close');
    Route::get('/available', [StationController::class, 'getAvailableStations'])->name('available');
});

// Rotte per gli operatori (protette dal middleware CheckStationAccess invece di auth)
Route::middleware([CheckStationAccess::class])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
    Route::post('/station-status', [OperatorController::class, 'setStationStatus'])->name('station-status');
    Route::post('/call-next', [OperatorController::class, 'callNextTicket'])->name('call-next');
    Route::post('/start-ticket', [OperatorController::class, 'startTicket'])->name('start-ticket');
    Route::post('/complete-ticket', [OperatorController::class, 'completeTicket'])->name('complete-ticket');
    Route::post('/transfer-ticket', [OperatorController::class, 'transferTicket'])->name('transfer-ticket');
    Route::post('/abandon-ticket', [OperatorController::class, 'abandonTicket'])->name('abandon-ticket');
    Route::post('/recall-ticket', [OperatorController::class, 'recallTicket'])->name('recall-ticket');
});

