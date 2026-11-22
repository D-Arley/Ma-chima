<?php

use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\VentaController;
use App\Http\Middleware\RequireTemporaryPasswordChange;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', RequireTemporaryPasswordChange::class])->name('dashboard');

Route::middleware(['auth', RequireTemporaryPasswordChange::class])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/catalogo', [CatalogoController::class, 'index'])->can('view-catalog')->name('catalogo.index');
    Route::get('/catalogo/{producto}', [CatalogoController::class, 'show'])->can('view-catalog')->name('catalogo.show');

    Route::middleware('can:manage-users')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::patch('/usuarios/{usuario}/rol', [UsuarioController::class, 'updateRole'])->name('usuarios.update-role');
        Route::patch('/usuarios/{usuario}/clave-temporal', [UsuarioController::class, 'temporaryPassword'])->name('usuarios.temporary-password');
        Route::get('/roles', [UsuarioController::class, 'roles'])->name('roles.index');
    });

    Route::middleware('can:manage-inventory')->prefix('inventario')->name('inventario.')->group(function () {
        Route::get('/', [InventarioController::class, 'index'])->name('index');
        Route::get('/crear', [InventarioController::class, 'create'])->name('create');
        Route::post('/', [InventarioController::class, 'store'])->name('store');
        Route::get('/{producto}/editar', [InventarioController::class, 'edit'])->name('edit');
        Route::patch('/{producto}', [InventarioController::class, 'update'])->name('update');
        Route::delete('/{producto}', [InventarioController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('can:count-inventory')->prefix('logistica')->name('logistica.')->group(function () {
        Route::get('/', [StockController::class, 'index'])->name('index');
        Route::post('/entregas', [StockController::class, 'deliver'])->middleware('can:deliver-stock')->name('deliver');
        Route::patch('/entregas/{transferencia}/recibir', [StockController::class, 'receive'])->middleware('can:receive-stock')->name('receive');
        Route::post('/conteos', [StockController::class, 'startCount'])->name('count-runs.start');
        Route::get('/conteos/{countRun}', [StockController::class, 'showCount'])->name('count-runs.show');
        Route::put('/conteos/{countRun}', [StockController::class, 'finishCount'])->name('count-runs.finish');
        Route::patch('/conteos/{countRun}/revision', [StockController::class, 'reviewCount'])->middleware('can:manage-inventory')->name('review-count');
    });

    Route::middleware('can:open-pos')->prefix('ventas')->name('ventas.')->group(function () {
        Route::get('/', [VentaController::class, 'index'])->name('index');
        Route::post('/', [VentaController::class, 'store'])->name('store');
        Route::get('/{venta}', [VentaController::class, 'show'])->name('show');
    });

    Route::middleware('can:manage-cash')->prefix('caja')->name('caja.')->group(function () {
        Route::get('/', [CajaController::class, 'index'])->name('index');
        Route::post('/abrir', [CajaController::class, 'open'])->name('open');
        Route::post('/transferir', [CajaController::class, 'transfer'])->name('transfer');
    });

    Route::middleware('can:view-movement-history')->prefix('movimientos')->name('movimientos.')->group(function () {
        Route::get('/', [MovimientoController::class, 'index'])->name('index');
        Route::get('/exportar/excel', [MovimientoController::class, 'exportExcel'])->name('export.excel');
        Route::get('/exportar/csv', [MovimientoController::class, 'exportCsv'])->name('export.csv');
        Route::get('/exportar/pdf', [MovimientoController::class, 'exportPdf'])->name('export.pdf');
    });
});

require __DIR__.'/auth.php';
