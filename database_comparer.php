<?php
/*
 * database_comparer.php
 *
 * Author: Jasdy Syarman Mohd Saari (https://github.com/akutaktau)
 * License: MIT License
 *
 * Remarks:
 * - This script is a command-line PHP tool for comparing a specific column's values between two MySQL databases for a given table and key column.
 * - It interactively prompts the user for connection details for both databases, the table name, the column to compare, the key column, and an optional output file for mismatches.
 * - The script connects to both databases, fetches all rows from the source, and checks for missing or mismatched values in the target database.
 * - Mismatches or missing rows are printed to the console and optionally saved to a file.
 *
 * How to use:
 * 1. Run the script in a terminal: php database_comparer.php
 * 2. Enter the required database connection details and table/column info when prompted.
 * 3. Optionally, provide a file path to save mismatches, or leave blank to skip file output.
 * 4. Review the output in the terminal and/or the specified file.
 *
 * Limitations:
 * - Only supports MySQL databases via PDO.
 * - Compares a single table and a single column at a time.
 * - Assumes the key column uniquely identifies rows.
 * - Does not handle large tables efficiently (loads all source rows, compares one-by-one).
 * - No support for complex data types, composite keys, or advanced comparison logic.
 * - No SSL or advanced connection options.
 * - No support for comparing multiple columns or tables in one run.
 * - Error handling is basic; connection or query errors will halt execution.
 */

class DatabaseComparer
{
    private $db1;
    private $db2;
    private $table;
    private $column;
    private $keyColumn;
    private $outputFile;
    private $fileHandle = null;

    private function prompt(string $question, bool $required = true): string
    {
        do {
            $input = readline($question . ': ');
            if (!$required || $input !== '') {
                return $input;
            }
            echo "This field is required.\n";
        } while (true);
    }

    public function collectInputs()
    {
        echo "==== Database 1 (Source) ====\n";
        $db1Host = $this->prompt('Host (e.g., localhost)');
        $db1Name = $this->prompt('Database Name');
        $db1User = $this->prompt('Username');
        $db1Pass = $this->prompt('Password');

        echo "\n==== Database 2 (Target) ====\n";
        $db2Host = $this->prompt('Host (e.g., localhost)');
        $db2Name = $this->prompt('Database Name');
        $db2User = $this->prompt('Username');
        $db2Pass = $this->prompt('Password');

        echo "\n==== Table & Column Settings ====\n";
        $this->table = $this->prompt('Table Name');
        $this->column = $this->prompt('Column Name to Compare');
        $this->keyColumn = $this->prompt('Key Column Name (Primary Key / Unique Key)');
        $this->outputFile = $this->prompt('Output file path to save mismatches (leave blank to skip)', false);

        $this->db1 = new PDO(
            "mysql:host=$db1Host;dbname=$db1Name;charset=utf8mb4",
            $db1User,
            $db1Pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $this->db2 = new PDO(
            "mysql:host=$db2Host;dbname=$db2Name;charset=utf8mb4",
            $db2User,
            $db2Pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function compare()
    {
        echo "\nStarting comparison...\n";

        $sql1 = "SELECT `{$this->keyColumn}`, `{$this->column}` FROM `{$this->table}` ORDER BY `{$this->keyColumn}` ASC";
        $sql2 = "SELECT `{$this->keyColumn}`, `{$this->column}` FROM `{$this->table}` WHERE `{$this->keyColumn}` = :key";

        $stmt1 = $this->db1->query($sql1);
        $stmt2 = $this->db2->prepare($sql2);

        // Always require output file
        if (empty($this->outputFile)) {
            $this->outputFile = 'dbcompare_mismatches_' . date('Ymd_His') . '.txt';
            echo "No output file specified. Using default: {$this->outputFile}\n";
        }
        $this->fileHandle = fopen($this->outputFile, 'w');
        if (!$this->fileHandle) {
            echo "Failed to open output file: {$this->outputFile}\n";
            exit(1);
        }

        $countMismatch = 0;
        while ($row1 = $stmt1->fetch()) {
            $keyValue = $row1[$this->keyColumn];
            $value1 = $row1[$this->column];

            $stmt2->execute(['key' => $keyValue]);
            $row2 = $stmt2->fetch();

            if (!$row2) {
                $message = "Missing in DB2 at {$this->keyColumn} = $keyValue\n";
            } else {
                $value2 = $row2[$this->column];
                if ($value1 != $value2) {
                    $message = "Mismatch at {$this->keyColumn} = $keyValue: DB1='$value1' vs DB2='$value2'\n";
                } else {
                    $message = null;
                }
            }

            if (isset($message)) {
                fwrite($this->fileHandle, $message);
                $countMismatch++;
            }
        }

        fclose($this->fileHandle);

        echo "\nComparison completed. Total mismatches: $countMismatch\n";
        echo "Results saved to: {$this->outputFile}\n";
    }

    public function run()
    {
        echo "==== Database 1 (Source) ====".PHP_EOL;
        $db1Host = $this->prompt('Host (e.g., localhost)');
        $db1Name = $this->prompt('Database Name');
        $db1User = $this->prompt('Username');
        $db1Pass = $this->prompt('Password');

        echo "\n==== Database 2 (Target) ====".PHP_EOL;
        $db2Host = $this->prompt('Host (e.g., localhost)');
        $db2Name = $this->prompt('Database Name');
        $db2User = $this->prompt('Username');
        $db2Pass = $this->prompt('Password');

        $this->db1 = new PDO(
            "mysql:host=$db1Host;dbname=$db1Name;charset=utf8mb4",
            $db1User,
            $db1Pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $this->db2 = new PDO(
            "mysql:host=$db2Host;dbname=$db2Name;charset=utf8mb4",
            $db2User,
            $db2Pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        echo "\n==== Operation ====".PHP_EOL;
        echo "1. Compare Schema (all tables)\n2. Compare Schema (single table)\n3. Compare Data\n";
        $choice = $this->prompt('Choose operation (1/2/3)');
        if ($choice === '1') {
            $outputFile = $this->prompt('Output file path to save schema comparison (leave blank to skip)', false);
            $this->compareSchemaAllTables($outputFile);
            exit(0);
        } elseif ($choice === '2') {
            $table = $this->prompt('Table Name');
            $outputFile = $this->prompt('Output file path to save schema comparison (leave blank to skip)', false);
            $this->compareSchemaSingleTable($table, false, $outputFile);
            exit(0);
        } elseif ($choice === '3') {
            $this->collectInputsForData();
            $this->compare();
            exit(0);
        } else {
            echo "Invalid choice. Exiting.\n";
            exit(1);
        }
    }

    private function collectInputsForData()
    {
        echo "\n==== Table & Column Settings ====".PHP_EOL;
        $this->table = $this->prompt('Table Name');
        $this->column = $this->prompt('Column Name to Compare');
        $this->keyColumn = $this->prompt('Key Column Name (Primary Key / Unique Key)');
        $this->outputFile = $this->prompt('Output file path to save mismatches (leave blank to skip)', false);
    }

    private function compareSchemaAllTables($outputFile = '')
    {
        echo "\nComparing schema for all tables...\n";
        $tables1 = $this->db1->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $tables2 = $this->db2->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $allTables = array_unique(array_merge($tables1, $tables2));
        $output = "";
        foreach ($allTables as $table) {
            $output .= "\nTable: $table\n";
            $output .= $this->compareSchemaSingleTable($table, true);
        }
        if ($outputFile) {
            file_put_contents($outputFile, $output);
            echo "\nSchema comparison results saved to: $outputFile\n";
        }
    }

    private function compareSchemaSingleTable($table, $suppressHeader = false, $outputFile = '')
    {
        $output = '';
        if (!$suppressHeader) {
            echo "\nComparing schema for table '$table'...\n";
            $output .= "\nComparing schema for table '$table'...\n";
        }
        $sql = "SHOW COLUMNS FROM `{$table}`";
        $columns1 = $this->db1->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $columns2 = $this->db2->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $schema1 = [];
        foreach ($columns1 as $col) {
            $schema1[$col['Field']] = $col;
        }
        $schema2 = [];
        foreach ($columns2 as $col) {
            $schema2[$col['Field']] = $col;
        }
        $allFields = array_unique(array_merge(array_keys($schema1), array_keys($schema2)));
        $diffs = [];
        foreach ($allFields as $field) {
            if (!isset($schema1[$field])) {
                $diffs[] = "Column '$field' missing in DB1";
            } elseif (!isset($schema2[$field])) {
                $diffs[] = "Column '$field' missing in DB2";
            } elseif ($schema1[$field]['Type'] !== $schema2[$field]['Type']) {
                $diffs[] = "Column '$field' type mismatch: DB1='{$schema1[$field]['Type']}' vs DB2='{$schema2[$field]['Type']}'";
            }
        }
        if ($diffs) {
            echo "Schema differences found:\n";
            $output .= "Schema differences found:\n";
            foreach ($diffs as $d) {
                echo "  - $d\n";
                $output .= "  - $d\n";
            }
        } else {
            echo "Schemas match.\n";
            $output .= "Schemas match.\n";
        }
        if ($outputFile) {
            file_put_contents($outputFile, $output, FILE_APPEND);
            return '';
        }
        return $output;
    }
}

// Run the comparer
$comparer = new DatabaseComparer();
$comparer->run();
