<?php
class Database
{
    private $LOCAL_DB = 0;
    protected $dbc = NULL;

    public function getConnection()
    {
        if ($this->dbc === NULL) {
            try {
                if (!$this->LOCAL_DB) {
                    $host = 'localhost';
                    $dbname = 'db202202979';
                    $username = 'u202202979';
                    $password = 'u202202979';
                } else {
                    $host = 'localhost';
                    $dbname = 'dbStudentID';
                    $username = 'uStudentID';
                    $password = 'uStudentID';
                }

                $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                $this->dbc = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }

        return $this->dbc;
    }

    public function closeDB()
    {
        $this->dbc = NULL; // PDO closes connection when set to NULL
    }
}
?>
