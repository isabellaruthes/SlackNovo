<?php

// Used only by acceptance.cjs with a freshly allocated temporary database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = getenv('DB_DATABASE');
// Both sides resolved with realpath(): on Windows the temp dir may be a short (8.3) path and uses "\".
$temporarios = (realpath(sys_get_temp_dir()) ?: sys_get_temp_dir()).DIRECTORY_SEPARATOR.'slacknovo-acceptance-';
if (! $database || ! str_starts_with(realpath($database) ?: '', $temporarios)) {
    throw new RuntimeException('Acceptance fixture requires its own temporary database.');
}
if (config('database.default') !== 'sqlite'
    || realpath(config('database.connections.sqlite.database')) !== realpath($database)
    || config('database.connections.sqlite.url')) {
    throw new RuntimeException('Effective database configuration is not the isolated SQLite fixture. Clear configuration cache before testing.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
App\Models\User::create(['nome' => 'aceite-browser', 'senha' => Illuminate\Support\Facades\Hash::make(getenv('SLK_TEST_PASSWORD'))]);
$category = App\Models\Categoria::create(['nome' => 'Categoria de aceite']);
for ($i = 1; $i <= 26; $i++) {
    App\Models\Produto::create([
        'nome' => sprintf('Peça de aceite %02d', $i), 'tipo_produto' => 'roupa', 'estado' => 'novo',
        'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        'genero' => 'feminino', 'tamanho' => 'm', 'id_categoria' => $category->id,
    ]);
}
echo "Isolated fixture ready\n";
