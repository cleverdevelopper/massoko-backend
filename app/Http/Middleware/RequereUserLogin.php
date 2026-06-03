<?php
    namespace App\Http\Middleware;
    use \App\Session\Admin\LoginSession as  SessionAdminLogin;

    class RequereUserLogin{
         public function handle($request, $next){
            if(!SessionAdminLogin::isLoged()){
                $request->getRouter()->redirect('/admin/authentication');
            }
             return $next($request);
         }
    }
?>