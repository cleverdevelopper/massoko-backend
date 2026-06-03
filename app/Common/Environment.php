<?php
//classe responsavel por carregar todas a variaveis de ambiente
namespace App\Common;

class Environment
{
    //funcao responsavel por carregar as variaveis de ambiente do projecto
    public static function load($dir)
    {
        $envFile = $dir . '/.env';
        if (!file_exists($envFile)) {
            return false;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Remove espaços
            $line = trim($line);

            // Ignora comentários
            if (str_starts_with($line, '#')) {
                continue;
            }
            // Verifica se é KEY=VALUE
            if (!str_contains($line, '=')) {
                continue;
            }
            putenv($line);
            // Também opcionalmente salva no $_ENV e $_SERVER
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
