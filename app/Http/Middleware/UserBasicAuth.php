<?php
    namespace App\Http\Middleware;

    class UserBasicAuth {
        private function getBasicAuth(){
            
        }
        //metodo responsavel por validar o acesso via HTTP
        private function basicAuth($request){
            if($obUser = $this->getBasicAuth()){

            }

            throw new \Exception('Utilizador ou senha invalidos', 403);
        }

        //Metodo responsavel por executar as accoes do Middleware
        public function handle($request, $next){
            //altera o contentType para JSON
             $this->basicAuth($request);
            //Executa o proximo nivel de Middleware
           return $next($request);
        }
    }
?>