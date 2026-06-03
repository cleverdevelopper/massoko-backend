<?php
    namespace App\Http\Middleware;

    class Maintenance{
        //Metodo responsavel por executar as accoes do Middleware
        public function handle($request, $next){
            //Verifica o estado de manutencao de uma pagina
            if(getenv('MAINTENANCE') == 'true'){
                throw new \Exception('Página em manutenção. Tente novamente mais tarde.', 200);
            }
            //Executa o proximo nivel de Middleware
           return $next($request);
        }
    }
?>