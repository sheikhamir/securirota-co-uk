<?php
/**
 * Database Migration Runner
 * 
 * This script manages database migrations for the Security Rota system.
 * Usage: php migrate.php [command]
 * 
 * Commands:
 *   status    - Show migration status
 *   migrate   - Run pending migrations
 *   rollback  - Rollback last migration (not implemented)
 *   fresh     - Drop all tables and run all migrations (DANGEROUS)
 */

require_once __DIR__ . '/config/database.php';

class MigrationRunner {
    private $db;
    private $migrationsPath;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->migrationsPath = __DIR__ . '/migrations/';
        
        if (!$this->db) {
            die("Database connection failed\n");
        }
        
        $this->ensureMigrationsTable();
    }
    
    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            filename VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            execution_time_ms INT DEFAULT 0,
            INDEX idx_filename (filename),
            INDEX idx_executed_at (executed_at)
        )";
        
        $this->db->exec($sql);
    }
    
    public function getStatus() {
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        echo "Migration Status:\n";
        echo "================\n\n";
        
        foreach ($migrationFiles as $file) {
            $status = in_array($file, $executedMigrations) ? 'APPLIED' : 'PENDING';
            $timestamp = '';
            
            if ($status === 'APPLIED') {
                $stmt = $this->db->prepare("SELECT executed_at FROM migrations WHERE filename = ?");
                $stmt->execute([$file]);
                $result = $stmt->fetch();
                $timestamp = $result ? ' (' . $result['executed_at'] . ')' : '';
            }
            
            printf("%-50s %s%s\n", $file, $status, $timestamp);
        }
        
        $pendingCount = count($migrationFiles) - count($executedMigrations);
        echo "\nTotal migrations: " . count($migrationFiles) . "\n";
        echo "Applied: " . count($executedMigrations) . "\n";
        echo "Pending: " . $pendingCount . "\n";
    }
    
    public function migrate() {
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        $pendingMigrations = array_diff($migrationFiles, $executedMigrations);
        
        if (empty($pendingMigrations)) {
            echo "No pending migrations to run.\n";
            return;
        }
        
        echo "Running " . count($pendingMigrations) . " pending migrations...\n\n";
        
        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration);
        }
        
        echo "\nAll migrations completed successfully!\n";
    }
    
    private function runMigration($filename) {
        echo "Running migration: $filename... ";
        
        $startTime = microtime(true);
        $filepath = $this->migrationsPath . $filename;
        
        if (!file_exists($filepath)) {
            echo "FAILED - File not found\n";
            return false;
        }
        
        $sql = file_get_contents($filepath);
        
        try {
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $this->db->beginTransaction();
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->exec($statement);
                }
            }
            
            $executionTime = round((microtime(true) - $startTime) * 1000);
            
            // Record the migration
            $stmt = $this->db->prepare("INSERT INTO migrations (filename, execution_time_ms) VALUES (?, ?)");
            $stmt->execute([$filename, $executionTime]);
            
            $this->db->commit();
            
            echo "SUCCESS ({$executionTime}ms)\n";
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            echo "FAILED - " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function fresh() {
        echo "WARNING: This will drop ALL tables and rebuild the database.\n";
        echo "Type 'yes' to continue: ";
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim($line) !== 'yes') {
            echo "Operation cancelled.\n";
            return;
        }
        
        echo "Dropping all tables...\n";
        
        // Get all tables
        $stmt = $this->db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Disable foreign key checks
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Drop all tables
        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS `$table`");
            echo "Dropped table: $table\n";
        }
        
        // Re-enable foreign key checks
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "Running all migrations...\n\n";
        $this->migrate();
    }
    
    private function getMigrationFiles() {
        $files = glob($this->migrationsPath . '*.sql');
        $migrationFiles = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            // Skip old migration files that have been consolidated
            if (!preg_match('/^(2025_|create_site_rotas)/', $filename)) {
                $migrationFiles[] = $filename;
            }
        }
        
        sort($migrationFiles);
        return $migrationFiles;
    }
    
    private function getExecutedMigrations() {
        try {
            $stmt = $this->db->query("SELECT filename FROM migrations ORDER BY filename");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'status';
    $runner = new MigrationRunner();
    
    switch ($command) {
        case 'status':
            $runner->getStatus();
            break;
            
        case 'migrate':
            $runner->migrate();
            break;
            
        case 'fresh':
            $runner->fresh();
            break;
            
        default:
            echo "Usage: php migrate.php [command]\n";
            echo "Commands:\n";
            echo "  status   - Show migration status\n";
            echo "  migrate  - Run pending migrations\n";
            echo "  fresh    - Drop all tables and run all migrations (DANGEROUS)\n";
            break;
    }
} else {
    // Web interface (optional)
    echo "<h1>Migration Runner</h1>";
    echo "<p>This script should be run from the command line.</p>";
    echo "<p>Usage: <code>php migrate.php [command]</code></p>";
}
