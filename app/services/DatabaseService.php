<?php

namespace app\services;

class DatabaseService
{
    public $connection;
    private $config;

    public function __construct()
    {
        $this->config = $this->loadConfiguration();
        $this->connection = $this->setUpConnection();
    }

    public function executeQuery($query, $params = [])
    {
        // Prepare the query
        $stmt = $this->connection->prepare($query);

        // Check if the query is valid
        if ($stmt === false) {
            die('Invalid query: ' . $this->connection->error);
        }

        if (empty($params)) {
            $this->execute($stmt);
            return $stmt;
        }

        // We will get here only if we have parameters
        $stmt->bind_param(...$params);
        $this->execute($stmt);

        return $stmt;
    }

    public function fetchAll($stmt)
    {
        // Get the result
        $result = $stmt->get_result();

        // Fetch all the rows
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        // Free the result
        $result->free();

        return $rows;
    }

    private function setUpConnection()
    {
        $host = $this->config['connections']['mysql']['host'];
        $database = $this->config['connections']['mysql']['database'];
        $username = $this->config['connections']['mysql']['username'];
        $password = $this->config['connections']['mysql']['password'];

        $this->connection = new \mysqli($host, $username, $password, $database);

        if ($this->connection->connect_error) {
            die('Connection failed: ' . $this->connection->connect_error);
        }

        return $this->connection;
    }

    private function loadConfiguration()
    {
        return include __DIR__ . '/../config/database_config.php';
    }

    private function execute($stmt)
    {
        // Execute the query
        $result = $stmt->execute();

        // Check if the query was successful
        if ($stmt->error) {
            die('Error executing query: ' . $stmt->error);
        }

        return $result;
    }

    public function close()
    {
        $this->connection->close();
    }
}