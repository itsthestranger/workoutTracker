<?php
class DB {

        private $pdo;

        public function __construct($host, $port, $dbname, $username, $password) {

                $pdo = new PDO('pgsql:host='.$host.';port='.$port.';user='.$username.';password='.$password.';dbname='.$dbname);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo = $pdo;
        }

        public function query($query, $params = array()) {
                $statement = $this->pdo->prepare($query);
                $statement->execute($params);

                if (explode(' ', $query)[0] == 'SELECT') {
                $data = $statement->fetchAll();
                return $data;
                }
        }

}
