<?php
    namespace App\Http\Middleware;

    class Api{
        //Metodo responsavel por executar as accoes do Middleware
        public function handle($request, $next){
            //altera o contentType para JSON
            $request->getRouter()->setContentType('application/json');
            //Executa o proximo nivel de Middleware
           return $next($request);
        }
    }
?>