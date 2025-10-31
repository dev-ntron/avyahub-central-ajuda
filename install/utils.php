<?php
/**
 * Ajustes utilitários para o instalador
 */

// Normaliza BASE_PATH removendo '/install' do final, se presente
function normalizeBasePath($path){
    if ($path === null || $path === false) return '/';
    $path = '/' . ltrim(rtrim((string)$path,'/'),'/');
    if ($path === '//') $path = '/';
    // remover sufixo /install
    if ($path !== '/' && substr($path, -8) === '/install') {
        $path = substr($path, 0, -8);
        if ($path === '') { $path = '/'; }
    }
    return $path;
}
