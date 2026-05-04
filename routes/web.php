<?php

use App\Http\Controllers\Admin\CaixaController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\CorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FornecedorController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\ProdutoController;
use App\Http\Controllers\Admin\RelatorioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog.public');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('produtos', ProdutoController::class)->except(['show']);
        Route::resource('categorias', CategoriaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('cores', CorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('materiais', MaterialController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('fornecedores', FornecedorController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('caixa', [CaixaController::class, 'index'])->name('caixa.index');
        Route::post('caixa/saida', [CaixaController::class, 'registrarSaida'])->name('caixa.saida');
        Route::post('caixa/venda', [CaixaController::class, 'registrarVenda'])->name('caixa.venda');

        Route::get('relatorios/produtos', [RelatorioController::class, 'produtos'])->name('relatorios.produtos');
        Route::get('relatorios/exportar', [RelatorioController::class, 'exportar'])->name('relatorios.exportar');
    });
});
