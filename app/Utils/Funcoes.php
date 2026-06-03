<?php
    namespace App\Utils;
    
    class Funcoes {
        public static function Permition($index){
            //verifica a permissao do utilizador
            if(substr($_SESSION['admin']['utilizador']['permissoes'], $index, 1) == 1){
                return true;
            }else{
                return false;
            }
        }
    }
?>