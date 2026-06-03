<?php
    namespace App\Http\Middleware;
    use \App\Session\Admin\LoginSession as  SessionAdminLogin;

    class RequereUserLogout{
        public function handle($request, $next){
            if(SessionAdminLogin::isLoged()){
                $request->getRouter()->redirect('/admin/dashboard');
            }
            return $next($request);
         }
    }
?>