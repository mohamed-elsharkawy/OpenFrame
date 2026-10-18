<?php
/**
 * Front Office — Database (PDO)
 * Single PDO connection to MySQL, shared through db().
 */

/**
 * @return PDO
 */
function db()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ));
    } catch (PDOException $ex) {
        http_response_code(500);
        exit(
            '<h1>Database connection failed</h1>'
            . '<p>Could not connect to MySQL database <strong>' . e(DB_NAME) . '</strong> on <strong>'
            . e(DB_HOST . ':' . DB_PORT) . '</strong>.</p>'
            . '<p>Create the database by importing <code>database.sql</code> (phpMyAdmin or the mysql client), '
            . 'then check the credentials in <code>includes/config.php</code>.</p>'
            . '<p>Error: ' . e($ex->getMessage()) . '</p>'
        );
    }

    return $pdo;
}

/**
 * Fetch a single row.
 *
 * @return array|null
 */
function db_row($sql, $params = array())
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Fetch all rows.
 *
 * @return array
 */
function db_rows($sql, $params = array())
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Execute a statement, return rows affected.
 *
 * @return int
 */
function db_exec($sql, $params = array())
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount();
}

/**
 * Next auto-increment value of a table (used to preview the record number).
 *
 * @return int
 */
function db_next_id($table)
{
    $stmt = db()->prepare(
        'SELECT AUTO_INCREMENT AS next_id
           FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $stmt->execute(array(DB_NAME, $table));
    $row = $stmt->fetch();

    if ($row && (int) $row['next_id'] > 0) {
        return (int) $row['next_id'];
    }

    $max = db_row('SELECT MAX(id) AS max_id FROM `' . str_replace('`', '', $table) . '`');

    return $max && $max['max_id'] !== null ? ((int) $max['max_id'] + 1) : 1;
}
