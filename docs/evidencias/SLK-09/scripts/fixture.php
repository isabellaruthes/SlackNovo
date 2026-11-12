<?php

// UI audit fixture. Usage: php fixture.php <repo>. Only runs on its own temporary SQLite DB.
$repo = $argv[1];
require $repo.'/vendor/autoload.php';
$app = require $repo.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! str_contains((string) getenv('DB_DATABASE'), 'slk-audit-') || config('database.default') !== 'sqlite') {
    throw new RuntimeException('Fixture requires its own temporary SQLite database.');
}

Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
App\Models\User::create(['nome' => 'operador-teste', 'senha' => Illuminate\Support\Facades\Hash::make(getenv('SLK_TEST_PASSWORD'))]);
(new Database\Seeders\DemoCatalogSeeder)->run();

$produto = App\Models\Produto::query()->orderBy('id')->first();
$produto->update(['status' => 'vendido']);
App\Models\Venda::create([
    'nome' => $produto->nome, 'id_produto' => $produto->id, 'comprador' => 'Cliente Exemplo',
    'valor_unitario' => 50, 'valor_venda_total' => 50, 'valor_compra_total' => 20,
    'data_hora' => now(), 'reembolsada' => false,
]);
App\Models\SaidaCaixa::create(['valor' => 15, 'motivo' => 'Compra de cabides', 'data_saidacaixa' => now()]);

echo json_encode(['produto' => $produto->id, 'produtos' => App\Models\Produto::count()]), PHP_EOL;
