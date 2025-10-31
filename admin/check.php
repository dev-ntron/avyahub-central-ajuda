<?php
// Verificação de integridade do sistema (somente admin)
session_start();

require_once __DIR__ . '/../config.php';

// Conexão DB
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    $pdo = null;
    $db_error = $e->getMessage();
}

// Verificar login admin atual (compatível com sessão existente)
$admin_ok = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$admin_ok) {
    http_response_code(403);
    echo 'Acesso negado';
    exit;
}

$checks = [];

// 1) .env e flag de instalação
$checks['env_exists'] = [
    'label' => '.env existente',
    'status' => file_exists(__DIR__ . '/../.env'),
];
$checks['installed_flag'] = [
    'label' => 'Flag de instalação (install/.installed)',
    'status' => file_exists(__DIR__ . '/../install/.installed'),
];

// 2) Permissões .env
$envPerms = @substr(sprintf('%o', fileperms(__DIR__ . '/../.env')), -4);
$checks['env_perms'] = [
    'label' => 'Permissões .env (<= 0640)',
    'status' => $envPerms ? ((int)$envPerms <= 640) : false,
    'details' => $envPerms ?: 'desconhecido'
];

// 3) Pastas de escrita
foreach ([
    ['path' => __DIR__ . '/../uploads', 'label' => 'Pasta uploads/ gravável'],
    ['path' => __DIR__ . '/../assets', 'label' => 'Pasta assets/ gravável']
] as $dir) {
    $ok = is_dir($dir['path']) && is_writable($dir['path']);
    $checks['writable_' . basename($dir['path'])] = [
        'label' => $dir['label'],
        'status' => $ok
    ];
}

// 4) Conexão DB + latência + tabelas
$db_latency_ms = null;
$tables_ok = false;
$table_counts = [];
$db_version = null;
if ($pdo) {
    $t0 = microtime(true);
    try {
        $v = $pdo->query('SELECT VERSION() as v')->fetch();
        $db_version = $v ? $v['v'] : 'desconhecida';
        $pdo->query('SELECT 1')->fetch();
        $db_latency_ms = round((microtime(true) - $t0) * 1000, 2);
        // Tabelas essenciais
        $need = ['categories','articles','site_settings','search_index'];
        $missing = [];
        foreach ($need as $t) {
            $st = $pdo->query("SHOW TABLES LIKE '".$t."'");
            if ($st->rowCount() === 0) { $missing[] = $t; }
            else {
                $c = $pdo->query("SELECT COUNT(*) c FROM `".$t."`")->fetch();
                $table_counts[$t] = (int)($c['c'] ?? 0);
            }
        }
        $tables_ok = count($missing) === 0;
        $checks['db_tables'] = [
            'label' => 'Tabelas essenciais existem',
            'status' => $tables_ok,
            'details' => $tables_ok ? 'ok' : ('faltando: ' . implode(', ', $missing))
        ];
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

// 5) PHP e extensões
$checks['php_version'] = [
    'label' => 'PHP >= 7.4',
    'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'details' => PHP_VERSION
];
foreach (['pdo_mysql','json','gd','mbstring'] as $ext) {
    $checks['ext_'.$ext] = [
        'label' => 'Extensão '.$ext,
        'status' => extension_loaded($ext)
    ];
}

// Render simples
function badge($ok){ return $ok ? '<span style="color:#065f46;background:#ecfdf5;border:1px solid #10b981;padding:2px 6px;border-radius:6px;">OK</span>' : '<span style="color:#991b1b;background:#fef2f2;border:1px solid #ef4444;padding:2px 6px;border-radius:6px;">FALHA</span>'; }
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificação de Integridade</title>
<style>
 body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f8fafc;margin:0;padding:2rem;color:#1f2937}
 .card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1.25rem;margin:0 auto 1rem;max-width:900px}
 h1{margin:0 0 1rem 0;font-size:1.5rem}
 table{width:100%;border-collapse:collapse}
 th,td{padding:.6rem;border-bottom:1px solid #e5e7eb;text-align:left}
 th{background:#f9fafb}
 .subtitle{color:#6b7280;margin:.25rem 0 1rem}
 .muted{color:#6b7280}
 .grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
 @media(max-width:768px){.grid{grid-template-columns:1fr}}
 .btn{display:inline-block;background:#2563eb;color:#fff;padding:.6rem 1rem;border-radius:8px;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <h1>Verificação de Integridade</h1>
  <div class="subtitle">Cheque rápido do ambiente, permissões e banco de dados.</div>
  <div class="grid">
    <div class="card">
      <h3 style="margin-top:0">Arquivos & Permissões</h3>
      <table>
        <tr><th>Item</th><th>Status</th><th>Detalhes</th></tr>
        <tr><td>.env existente</td><td><?= badge($checks['env_exists']['status']) ?></td><td class="muted"></td></tr>
        <tr><td>Flag install/.installed</td><td><?= badge($checks['installed_flag']['status']) ?></td><td class="muted"></td></tr>
        <tr><td>Permissões .env (<=0640)</td><td><?= badge($checks['env_perms']['status']) ?></td><td class="muted"><?= htmlspecialchars($checks['env_perms']['details'] ?? '-') ?></td></tr>
        <tr><td>uploads/ gravável</td><td><?= badge($checks['writable_uploads']['status']) ?></td><td class="muted"></td></tr>
        <tr><td>assets/ gravável</td><td><?= badge($checks['writable_assets']['status']) ?></td><td class="muted"></td></tr>
      </table>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Banco de Dados</h3>
      <table>
        <tr><th>Item</th><th>Status</th><th>Detalhes</th></tr>
        <tr><td>Conexão</td><td><?= badge($pdo!==null) ?></td><td class="muted"><?= isset($db_error)? htmlspecialchars($db_error):'ok' ?></td></tr>
        <tr><td>Latência</td><td><?= badge($pdo!==null) ?></td><td class="muted"><?= $db_latency_ms!==null? $db_latency_ms.' ms':'-' ?></td></tr>
        <tr><td>Versão MySQL</td><td><?= badge($pdo!==null) ?></td><td class="muted"><?= htmlspecialchars($db_version ?? '-') ?></td></tr>
        <tr><td>Tabelas essenciais</td><td><?= badge($checks['db_tables']['status'] ?? false) ?></td><td class="muted"><?= htmlspecialchars($checks['db_tables']['details'] ?? '-') ?></td></tr>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <h3 style="margin-top:0">Contagem de Registros</h3>
  <table>
    <tr><th>Tabela</th><th>Registros</th></tr>
    <?php foreach ($table_counts as $t=>$c): ?>
    <tr><td><?= htmlspecialchars($t) ?></td><td><?= (int)$c ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h3 style="margin-top:0">Ambiente</h3>
  <table>
    <tr><th>Item</th><th>Status</th><th>Detalhes</th></tr>
    <tr><td>PHP >= 7.4</td><td><?= badge($checks['php_version']['status']) ?></td><td class="muted"><?= htmlspecialchars($checks['php_version']['details']) ?></td></tr>
    <?php foreach (['pdo_mysql','json','gd','mbstring'] as $ext): ?>
      <tr><td>Extensão <?= $ext ?></td><td><?= badge($checks['ext_'.$ext]['status']) ?></td><td class="muted"></td></tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card" style="text-align:center">
  <a href="/admin/check.php" class="btn">Reexecutar Testes</a>
  <a href="/admin" class="btn" style="background:#059669;margin-left:.5rem">Voltar ao Admin</a>
</div>
</body>
</html>
