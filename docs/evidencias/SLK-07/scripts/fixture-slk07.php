<?php

// SLK-07 browser evidence fixture. Usage: php fixture-slk07.php <repo>
// Runs only against the temporary SQLite database given in DB_DATABASE (never the local app DB).
$repo = $argv[1];
require $repo.'/vendor/autoload.php';
$app = require $repo.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$database = (string) getenv('DB_DATABASE');
if (! str_contains($database, 'slk07-browser-') || config('database.default') !== 'sqlite') {
    throw new RuntimeException('Fixture requires its own temporary SQLite database.');
}

Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
App\Models\User::create(['nome' => 'operador-teste', 'senha' => Illuminate\Support\Facades\Hash::make(getenv('SLK_TEST_PASSWORD'))]);

$produto = fn (array $data) => App\Models\Produto::create($data + [
    'estado' => 'novo', 'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel', 'genero' => 'unissex',
]);

$vendido = $produto(['nome' => 'Camisa vendida (BUG-14)', 'tipo_produto' => 'roupa', 'tamanho' => 'g', 'status' => 'vendido']);
App\Models\Venda::create([
    'nome' => 'Camisa vendida (BUG-14)', 'id_produto' => $vendido->id, 'comprador' => 'Cliente Exemplo',
    'valor_unitario' => 30, 'valor_venda_total' => 30, 'valor_compra_total' => 10,
    'data_hora' => now(), 'reembolsada' => false,
]);
$roupa = $produto(['nome' => 'Camisa M (BUG-09)', 'tipo_produto' => 'roupa', 'tamanho' => 'm']);
$calcado = $produto(['nome' => 'Tênis 38 (BUG-09)', 'tipo_produto' => 'calcado', 'tamanho' => '38']);
$consignado = $produto(['nome' => 'Vestido consignado (BUG-07)', 'tipo_produto' => 'roupa', 'tamanho' => 'p']);

echo json_encode([
    'vendido' => $vendido->id, 'roupa' => $roupa->id, 'calcado' => $calcado->id, 'consignado' => $consignado->id,
]), PHP_EOL;
