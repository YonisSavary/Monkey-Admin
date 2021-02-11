<?php 

namespace Controllers;

use Monkey\Config;
use Monkey\Dist\DB;
use Monkey\Web\Request;
use Monkey\Web\Response;
use PDO;
use Monkey\Web\API;

class m_api_model
{
    public static function clean_name(string $name)
    {
        $name = ucfirst($name);
        $upper = false;
        $letters = str_split($name);
        $first = true;
        foreach($letters as &$char)
        {
            if ($upper === true)
            {
                $char = strtoupper($char);
                $upper = false;
            }
            if ($char == "_") {
                $upper = true;
                if (!$first) $char = "";
            }
            if ($first === true) $first = false;
        }
        return join("", $letters);
    }
    

    public static function get_model_names()
    {
        $classes = get_declared_classes();
        $classes = array_filter($classes, function(string $class_name)
        {
            return strpos($class_name, "Models\\");
        });
        return $classes;
    }

    public static function model_exists(string $table)
    {
        $class_name = m_api_model::clean_name($table);
        $class_with_namespace = "Models\\$class_name";
        return class_exists($class_with_namespace, false);
    }

    public static function get_model_path(string $table_name)
    {
        $class_name = m_api_model::clean_name($table_name);
        $class_with_namespace = "Models\\$class_name";
        if (class_exists($class_with_namespace, false))
        {
                $r = new \ReflectionClass($class_with_namespace);
                return ["table"=>$table_name, "model"=>$class_name, "path"=>$r->getFileName(), "fetched"=> true];
        }
        return ["table"=>$table_name, "model"=>$class_name, "path"=>null, "fetched"=>false];
    }

    public static function get_dist_tables()
    {
        $fetched = DB::query("SHOW TABLES", PDO::FETCH_BOTH);
        return array_map(function($element)
        {
            return $element[0];
        }, $fetched);
    }

    public static function fetch_model(string $table_name, string $path)
    {
        $model_name = m_api_model::clean_name($table_name);
        
        
        $primary_key = null;
        $fields_str = "";
        $fields = DB::query("DESCRIBE $table_name");
        foreach ($fields as $f)
        {
            $fields_str .= "\tpublic \$".$f["Field"].";\n";
            if ($f["Key"] === "PRI")
            {
                $primary_key = $f["Field"];
            }
        }

        $model = "<?php

namespace Models;

use Monkey\Model;

/* AUTOGENERATED BY m_api_model.php */
class $model_name extends Model {
    protected \$table=\"$table_name\";
";

        if (!is_null($primary_key)) $model .= "\tprotected \$primary_key=\"".$primary_key."\";\n";
        $model.= $fields_str;

        $model .="}";
        
        file_put_contents($path, $model);
    }



    /**
     * @api
     */
    public function create(Request $req): Response
    {
        $params = API::retrieve($req, ["table"]);
        $table = $params["table"];
        $tables = m_api_model::get_dist_tables();
        if (!in_array($table, $tables)) API::error("Inexistant table !");

        $model_name = m_api_model::clean_name($table);
        if (m_api_model::model_exists($table))
        {
            $path = m_api_model::get_model_path($table)["path"];
        } else {
            $app_dir = Config::get("app_directory");
            if (substr($app_dir, -1) !== "/") $app_dir .= "/";
            $path = $app_dir. "models/".$model_name.".php";
        }
        
        m_api_model::fetch_model($table, $path);

        return API::ok();
    }



    /**
     * @api
     */
    public function read(): Response
    {
        $dist_tables = m_api_model::get_dist_tables();
        $tables = [];
        foreach ($dist_tables as &$element)
        {
            array_push($tables, m_api_model::get_model_path($element));
        }
        return Response::json($tables);
    }



    /**
     * @api
     */
    public function delete(Request $req): Response
    {
        $params = API::retrieve($req, ["model"]);
        $model = "Models\\".$params["model"];
        if (!class_exists($model, false)) API::error("Inexistant model !");
        $r = new \ReflectionClass($model);
        $path = $r->getFileName();
        unlink($path);
        return API::ok();
    }

}