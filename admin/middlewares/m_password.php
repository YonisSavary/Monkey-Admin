<?php 

namespace Middlewares;

use Monkey\Router;
use Monkey\Web\Request;

class m_password 
{
    public function handle()
    {
        session_start();
        if (!isset($_SESSION["m_admin_logged"]) || $_SESSION["m_admin_logged"] === false){
            return Router::redirect(router("m_guard_page"));
        }
    }
}