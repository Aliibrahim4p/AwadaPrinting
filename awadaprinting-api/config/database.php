<?php
class Database
{
    private $host = "localhost";
    private $db_name = "AwadaPrintingServices";  // e.g. manufacturing_system
    private $username = "postgres";           // your PostgreSQL username
    private $password = "ali@123";      // your PostgreSQL password
    public $conn;

    public function connect()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'utf8'");
        } catch (PDOException $e) {
            echo "Database connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>