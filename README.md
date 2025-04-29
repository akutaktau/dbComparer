# Database Comparer

This script is a command-line PHP tool for comparing a specific column's values between two MySQL databases for a given table and key column.

## How to use

1. Run the script in a terminal: `php database_comparer.php`
2. Enter the required database connection details and table/column info when prompted.
3. Optionally, provide a file path to save mismatches, or leave blank to skip file output.
4. Review the output in the terminal and/or the specified file.

## Requirements

* PHP 7.0 or higher
* MySQL databases
* PDO extension enabled in PHP

## Limitations

* Only supports MySQL databases via PDO.
* Compares a single table and a single column at a time.
* Assumes the key column uniquely identifies rows.
* Does not handle large tables efficiently (loads all source rows, compares one-by-one).
* No support for complex data types, composite keys, or advanced comparison logic.
* No SSL or advanced connection options.
* No support for comparing multiple columns or tables in one run.
* Error handling is basic; connection or query errors will halt execution.

## License

This project is licensed under the MIT License - see the `LICENSE` file for details.
