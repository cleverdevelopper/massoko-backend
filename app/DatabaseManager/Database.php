<?php
    namespace App\DatabaseManager;
    use \PDO;
    use \PDOException;

    class Database{
        private static $host;
        private static $name;
        private static $user;
        private static $pass;
        private static $port;
        private $table;
        private $connection;

        public static function config($host,$name,$user,$pass,$port){
            self::$host = $host;
            self::$name = $name;
            self::$user = $user;
            self::$pass = $pass;
            self::$port = $port;
        }

        public function __construct($table = null){
            $this->table = $table;
            $this->setConnection();
        }

        private function setConnection(){
            try{
            $this->connection = new PDO('mysql:host='.self::$host.';dbname='.self::$name.';port='.self::$port,self::$user,self::$pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            }catch(PDOException $e){
            die('ERROR: '.$e->getMessage());
            }
        }

        public function execute($query,$params = []){
            try{
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            return $statement;
            }catch(PDOException $e){
            die('ERROR: '.$e->getMessage());
            }
        }

        // ==============================================
        // EXECUTAR SQL CRU COM JOIN
        // ==============================================
        public function executeCustomQuery($query){
            return $this->execute($query);
        }

        public function insert($values){
            $fields = array_keys($values);
            $binds  = array_pad([],count($fields),'?');

            $query = 'INSERT INTO '.$this->table.' ('.implode(',',$fields).') VALUES ('.implode(',',$binds).')';

            $this->execute($query,array_values($values));

            return $this->connection->lastInsertId();
        }

        public function select($where = null, $order = null, $limit = null, $fields = '*', $group = null){
            if (is_array($order)) {
                $order = implode(', ', $order);
            }

            $where = $where ? 'WHERE '.$where : '';
            $group = $group ? 'GROUP BY ' . $group : '';
            $order = $order ? 'ORDER BY '.$order : '';
            $limit = $limit ? 'LIMIT '.$limit : '';

            $query = 'SELECT '.$fields.' FROM '.$this->table.' '.$where. ' ' . $group . ' '.$order.' '.$limit;

            return $this->execute($query);
        }

        public function update($where,$values){
            $fields = array_keys($values);

            $query = 'UPDATE '.$this->table.' SET '.implode('=?,',$fields).'=? WHERE '.$where;

            $this->execute($query,array_values($values));

            return true;
        }

        public function delete($where){
            $query = 'DELETE FROM '.$this->table.' WHERE '.$where;
            
            $this->execute($query);

            return true;
        }

         #========================================================
        # Funcoes relacionados com as transacoes
        #========================================================
        // Começar transação
        public function beginTransaction() {
            return $this->connection->beginTransaction();
        }

        // Confirmar transação
        public function commit() {
            return $this->connection->commit();
        }

        // Cancelar transação
        public function rollBack() {
            return $this->connection->rollBack();
        }

        // Pegar a conexão PDO diretamente (caso precise usar fora)
        public function getConnection() {
            return $this->connection;
        }

    }

?>