<?php
/**
 * OpenFrame — Database layer (PDO)
 * ---------------------------------
 * One lazily-created PDO connection shared by the whole request, plus a
 * small set of helpers that cover the four CRUD operations.
 *
 * All helpers use prepared statements, so values are never interpolated
 * into SQL. Table and column names are validated before they are used as
 * identifiers (see db_ident()).
 *
 * Connection settings come from includes/config.php
 * (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET).
 * Load with: require_once __DIR__ . '/includes/db.php';
 */

/* Fallbacks so this file also works when config.php was not loaded. */
if (!defined('DB_HOST'))    { define('DB_HOST', '127.0.0.1'); }
if (!defined('DB_PORT'))    { define('DB_PORT', 3306); }
if (!defined('DB_NAME'))    { define('DB_NAME', 'openframe'); }
if (!defined('DB_USER'))    { define('DB_USER', 'root'); }
if (!defined('DB_PASS'))    { define('DB_PASS', ''); }
if (!defined('DB_CHARSET')) { define('DB_CHARSET', 'utf8mb4'); }

/**
 * The shared PDO connection.
 *
 * The connection is opened on first use, so a page that never touches the
 * database still renders when MySQL is down.
 *
 * @return PDO
 */
function db()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
         . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

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
            . '<p>Could not connect to <strong>' . e(DB_NAME) . '</strong> on <strong>'
            . e(DB_HOST . ':' . DB_PORT) . '</strong>.</p>'
            . '<p>Create the database by importing <code>database.sql</code>, then check the '
            . 'credentials in <code>includes/config.php</code>.</p>'
            . '<p>Error: ' . e($ex->getMessage()) . '</p>'
        );
    }

    return $pdo;
}

/**
 * Quote and validate a table/column name for use as a SQL identifier.
 *
 * Only letters, digits and underscores are accepted, so an identifier can
 * never carry SQL with it. Throws InvalidArgumentException otherwise.
 *
 * @return string  e.g. `guest_visits`
 */
function db_ident($name)
{
    $name = (string) $name;

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
        throw new InvalidArgumentException('Invalid SQL identifier: ' . $name);
    }

    return '`' . $name . '`';
}

/**
 * Fetch one row.
 *
 *   $user = db_row('SELECT * FROM users WHERE id = ?', array($id));
 *
 * @return array|null  null when nothing matched
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
 *   $rows = db_rows('SELECT * FROM users ORDER BY full_name');
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
 * Run a statement (INSERT / UPDATE / DELETE / DDL).
 *
 * @return int  number of affected rows
 */
function db_exec($sql, $params = array())
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount();
}

/**
 * Fetch a single value (first column of the first row).
 *
 *   $count = db_column('SELECT COUNT(*) FROM users');
 *
 * @return mixed|null
 */
function db_column($sql, $params = array())
{
    $row = db_row($sql, $params);

    if ($row === null) {
        return null;
    }

    return reset($row);
}

/* ------------------------------------------------------------------
 * CRUD helpers
 * ------------------------------------------------------------------ */

/**
 * INSERT a row from an associative array and return the new id.
 *
 *   $id = db_insert('users', array(
 *       'full_name' => 'Sara',
 *       'username'  => 'sara',
 *   ));
 *
 * @return int  the AUTO_INCREMENT value
 */
function db_insert($table, array $data)
{
    if (!$data) {
        throw new InvalidArgumentException('db_insert() needs at least one column.');
    }

    $columns = array();
    $marks   = array();
    $params  = array();

    foreach ($data as $column => $value) {
        $columns[] = db_ident($column);
        $marks[]   = '?';
        $params[]  = $value;
    }

    db_exec(
        'INSERT INTO ' . db_ident($table)
        . ' (' . implode(', ', $columns) . ')'
        . ' VALUES (' . implode(', ', $marks) . ')',
        $params
    );

    return (int) db()->lastInsertId();
}

/**
 * UPDATE rows.
 *
 *   db_update('users', array('full_name' => 'Sara A.'), 'id = ?', array($id));
 *
 * The $where clause is raw SQL with ? placeholders. An empty $where
 * updates every row in the table — pass a condition in normal use.
 *
 * @return int  number of affected rows
 */
function db_update($table, array $data, $where = '', array $whereParams = array())
{
    if (!$data) {
        return 0;
    }

    $set    = array();
    $params = array();

    foreach ($data as $column => $value) {
        $set[]   = db_ident($column) . ' = ?';
        $params[] = $value;
    }

    $sql = 'UPDATE ' . db_ident($table) . ' SET ' . implode(', ', $set);

    if ($where !== '' && $where !== null) {
        $sql .= ' WHERE ' . $where;
    }

    return db_exec($sql, array_merge($params, $whereParams));
}

/**
 * DELETE rows.
 *
 *   db_delete('users', 'id = ?', array($id));
 *
 * An empty $where deletes every row — pass a condition in normal use.
 *
 * @return int  number of deleted rows
 */
function db_delete($table, $where = '', array $params = array())
{
    $sql = 'DELETE FROM ' . db_ident($table);

    if ($where !== '' && $where !== null) {
        $sql .= ' WHERE ' . $where;
    }

    return db_exec($sql, $params);
}

/**
 * Find a row by its primary key.
 *
 *   $user = db_find('users', 3);              // WHERE id = 3
 *   $dept = db_find('departments', 'sales', 'code');
 *
 * @return array|null
 */
function db_find($table, $id, $key = 'id')
{
    return db_row(
        'SELECT * FROM ' . db_ident($table) . ' WHERE ' . db_ident($key) . ' = ? LIMIT 1',
        array($id)
    );
}

/**
 * Does at least one row match?
 *
 *   if (db_exists('users', 'username = ?', array('sara'))) { ... }
 *
 * @return bool
 */
function db_exists($table, $where = '', array $params = array())
{
    $sql = 'SELECT 1 FROM ' . db_ident($table);

    if ($where !== '' && $where !== null) {
        $sql .= ' WHERE ' . $where;
    }

    $sql .= ' LIMIT 1';

    return db_row($sql, $params) !== null;
}

/**
 * Count rows.
 *
 *   $total = db_count('users');
 *   $open  = db_count('guest_visits', 'leave_time IS NULL');
 *
 * @return int
 */
function db_count($table, $where = '', array $params = array())
{
    $sql = 'SELECT COUNT(*) FROM ' . db_ident($table);

    if ($where !== '' && $where !== null) {
        $sql .= ' WHERE ' . $where;
    }

    return (int) db_column($sql, $params);
}

/**
 * Run a callback inside a transaction; rolls back on any exception.
 *
 *   db_transaction(function () {
 *       $id = db_insert('orders', array('total' => 10));
 *       db_insert('order_lines', array('order_id' => $id, 'qty' => 2));
 *   });
 *
 * @return mixed  whatever the callback returned
 */
function db_transaction(callable $callback)
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $result = $callback($pdo);
        $pdo->commit();

        return $result;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $ex;
    }
}

/**
 * Paginate a SELECT.
 *
 * Pass a query WITHOUT a LIMIT clause — the helper adds it.
 *
 *   $page = db_paginate(
 *       'SELECT * FROM guest_visits ORDER BY entry_time DESC',
 *       array(), 2, 25
 *   );
 *   // $page['rows'], 'total', 'page', 'per_page', 'pages'
 *
 * @return array
 */
function db_paginate($sql, array $params = array(), $page = 1, $perPage = 20)
{
    $page    = max(1, (int) $page);
    $perPage = max(1, (int) $perPage);

    $total = (int) db_column('SELECT COUNT(*) FROM (' . $sql . ') AS of_count', $params);

    $rows = db_rows(
        $sql . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
        $params
    );

    return array(
        'rows'     => $rows,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int) ceil($total / $perPage),
    );
}

/**
 * Next AUTO_INCREMENT value of a table (preview a record number).
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
    $stmt->execute(array(DB_NAME, (string) $table));
    $row = $stmt->fetch();

    if ($row && (int) $row['next_id'] > 0) {
        return (int) $row['next_id'];
    }

    $max = db_row('SELECT MAX(id) AS max_id FROM ' . db_ident($table));

    return $max && $max['max_id'] !== null ? ((int) $max['max_id'] + 1) : 1;
}
