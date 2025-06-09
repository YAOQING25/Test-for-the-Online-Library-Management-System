<?php

namespace Tests\Support\Config;

class TestDatabase
{
    private static $instance = null;
    private $pdo;
    private $inTransaction = false;

    private function __construct()
    {
        $this->pdo = new \PDO(
            'mysql:host=localhost;dbname=library_test',
            'root',
            '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
        if (!$this->inTransaction) {
            $this->pdo->beginTransaction();
            $this->inTransaction = true;
        }
    }

    public function commitTransaction(): void
    {
        if ($this->inTransaction) {
            $this->pdo->commit();
            $this->inTransaction = false;
        }
    }

    public function rollbackTransaction(): void
    {
        if ($this->inTransaction) {
            try {
                $this->pdo->rollBack();
            } catch (\PDOException $e) {
                // Ignore if there's no active transaction
                error_log("Rollback failed: " . $e->getMessage());
            }
            $this->inTransaction = false;
        }
    }

    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function resetTestDatabase(): void
    {
        try {
            // If we're in a transaction, roll it back first
            $this->rollbackTransaction();
            
            // Disable foreign key checks
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            // Get all tables in the database
            $stmt = $this->pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            // Reset all test tables
            $tables = ['tblauthors', 'tblbooks', 'tblcategory', 'tblissuedbookdetails', 'tblstudents', 'admin'];
            foreach ($tables as $table) {
                if (in_array($table, $existingTables)) {
                    try {
                        // Delete all records instead of truncate to avoid foreign key issues
                        $this->pdo->exec("DELETE FROM `$table`");
                        $this->pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
                    } catch (\PDOException $e) {
                        error_log("Failed to reset table $table: " . $e->getMessage());
                    }
                }
            }

            // Re-enable foreign key checks
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            // Seed any required base data
            $this->seedBaseData();

            // Start fresh transaction
            $this->beginTransaction();
        } catch (\PDOException $e) {
            error_log("Database reset failed: " . $e->getMessage());
            // Don't throw an exception, just log the error and continue
        }
    }

    private function seedBaseData(): void
    {
        try {
            // Add a default category for testing
            $stmt = $this->pdo->prepare('INSERT INTO tblcategory (CategoryName, Status) VALUES (?, ?)');
            $stmt->execute(['Test Category', 1]);
            
            // Add a default author for testing
            $stmt = $this->pdo->prepare('INSERT INTO tblauthors (AuthorName) VALUES (?)');
            $stmt->execute(['Test Author']);
        } catch (\PDOException $e) {
            error_log("Failed to seed data: " . $e->getMessage());
        }
    }

    public function prepare($sql): \PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function exec($sql): int|false
    {
        return $this->pdo->exec($sql);
    }

    public function lastInsertId($name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    public function quote($string): string|false
    {
        return $this->pdo->quote($string);
    }

    public function getAttribute($attribute): mixed
    {
        return $this->pdo->getAttribute($attribute);
    }

    public function setAttribute($attribute, $value): bool
    {
        return $this->pdo->setAttribute($attribute, $value);
    }
}