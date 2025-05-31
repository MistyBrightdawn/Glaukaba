<?php

class Database {
    private ?PDO $pdo = null;
    private string $dsn;
    private string $username;
    private string $password;
    private array $options;

    /**
     * Database constructor.
     * @param string $dsn The Data Source Name.
     * @param string $username The database username.
     * @param string $password The database password.
     * @param array $options Additional PDO connection options.
     */
    public function __construct(string $dsn, string $username, string $password, array $options = []) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options + [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->connect();
    }

    /**
     * Establishes the PDO connection.
     * @throws PDOException if the connection fails.
     */
    private function connect(): void {
        try {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            // Log error or handle more gracefully
            error_log("Database Connection Error: " . $e->getMessage());
            throw $e; // Re-throw the exception to be caught by the caller
        }
    }

    /**
     * Returns the PDO connection instance.
     * @return PDO The PDO instance.
     * @throws PDOException if the connection is not established.
     */
    public function getConnection(): PDO {
        if ($this->pdo === null) {
            // This should ideally not happen if constructor succeeded
            // but as a safeguard:
            $this->connect();
            if ($this->pdo === null) { // If connect still fails to set it
                 throw new PDOException("Failed to establish database connection.");
            }
        }
        return $this->pdo;
    }

    /**
     * Prepares and executes a SQL query.
     * @param string $sql The SQL query to execute.
     * @param array $params Parameters to bind to the query.
     * @return PDOStatement The PDOStatement object.
     * @throws PDOException on failure.
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " SQL: " . $sql . " Params: " . print_r($params, true));
            throw $e;
        }
    }

    /**
     * Fetches a single row from the database.
     * @param string $sql The SQL query.
     * @param array $params Parameters to bind.
     * @return array|false The row as an associative array, or false if no rows.
     * @throws PDOException on failure.
     */
    public function fetch(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all rows from the database.
     * @param string $sql The SQL query.
     * @param array $params Parameters to bind.
     * @return array An array of associative arrays.
     * @throws PDOException on failure.
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Executes an INSERT, UPDATE, or DELETE query.
     * @param string $sql The SQL query.
     * @param array $params Parameters to bind.
     * @return int The number of affected rows for UPDATE/DELETE, or 1 on successful INSERT (usually).
     *             For INSERTs, consider using lastInsertId() separately if needed.
     * @throws PDOException on failure.
     */
    public function execute(string $sql, array $params = []): int {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     * @param string|null $name Name of the sequence object from which the ID should be returned (for some drivers).
     * @return string|false The ID of the last inserted row, or false on failure.
     */
    public function lastInsertId(string $name = null) {
        return $this->getConnection()->lastInsertId($name);
    }

    /**
     * Checks if a table exists in the database.
     * The specific query might need adjustment based on the SQL dialect (MySQL, PostgreSQL, SQLite).
     * This example is for MySQL.
     *
     * @param string $tableName The name of the table to check.
     * @return bool True if the table exists, false otherwise.
     * @throws PDOException on query failure.
     */
    public function tableExists(string $tableName): bool {
        // This is a common way for MySQL. Other databases might require different queries.
        // For PostgreSQL: "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = :tableName)"
        // For SQLite: "SELECT name FROM sqlite_master WHERE type='table' AND name=:tableName"
        $sql = "SHOW TABLES LIKE :tableName";
        try {
            $stmt = $this->query($sql, ['tableName' => $tableName]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Table Exists Check Error: " . $e->getMessage());
            throw $e; // Or return false, depending on desired error handling
        }
    }

    /**
     * Returns the appropriate SQL string for an auto-incrementing primary key.
     * @return string SQL for auto-incrementing primary key.
     */
    private static function getSqlAutoincrement(): string {
        $dbType = defined('SQL_DBTYPE') ? SQL_DBTYPE : 'mysql';
        switch (strtolower($dbType)) {
            case 'pgsql':
                return 'SERIAL PRIMARY KEY';
            case 'sqlite':
                return 'INTEGER PRIMARY KEY AUTOINCREMENT';
            case 'mysql':
            default:
                return 'INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT';
        }
    }

    /**
     * Initializes the main posts table (SQL_TABLE_POSTS).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createPostsTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_POSTS') ? SQL_TABLE_POSTS : 'comments';
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            parent INTEGER,
            timestamp INTEGER,
            lasthit INTEGER,
            ip TEXT,
            id TEXT,
            date TEXT,
            name TEXT,
            trip TEXT,
            email TEXT,
            subject TEXT,
            password TEXT,
            comment TEXT,
            originalcomment TEXT,
            image TEXT,
            size INTEGER,
            md5 TEXT,
            width INTEGER,
            height INTEGER,
            thumbnail TEXT,
            tn_width INTEGER, -- Changed from TEXT
            tn_height INTEGER, -- Changed from TEXT
            sticky TINYINT,
            permasage TINYINT,
            locked TINYINT,
            filename TEXT,
            tnmask TINYINT,
            staffpost TINYINT,
            passnum INTEGER
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the admin table (SQL_TABLE_ADMIN).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createAdminTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_ADMIN') ? SQL_TABLE_ADMIN : 'admin';
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            type TEXT,
            comment TEXT,
            private TEXT,
            ival1 TEXT,
            ival2 TEXT,
            sval1 TEXT,
            fromuser TEXT,
            publicfromuser TEXT,
            duration INTEGER,
            perm TINYINT,
            scope TEXT,
            postnum INTEGER,
            board TEXT,
            warning TINYINT,
            timestamp INTEGER,
            active TINYINT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the users table (SQL_TABLE_USERS).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createUsersTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_USERS') ? SQL_TABLE_USERS : 'users';
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            user TEXT,
            pass TEXT,
            email TEXT,
            class TEXT,
            boards TEXT,
            lastip TEXT,
            lastdate INTEGER,
            newmsgs TINYINT,
            newreports TINYINT,
            newbanreqs TINYINT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the proxy table (SQL_TABLE_PROXY).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createProxyTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_PROXY') ? SQL_TABLE_PROXY : 'proxy';
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            type TEXT,
            ip TEXT,
            timestamp INTEGER,
            date TEXT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the reports table (SQL_TABLE_REPORTS).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createReportsTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_REPORTS') ? SQL_TABLE_REPORTS : 'reports';
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            postnum INTEGER,
            parent TINYINT, -- Inferred from usage, might need review (was not explicitly defined in schema, but used in code)
            board TEXT,
            fromip TEXT,
            timestamp INTEGER,
            vio TINYINT,
            spam TINYINT,
            illegal TINYINT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the messages table (SQL_TABLE_MESSAGE - from wakaba.pl SQL_MESSAGE_TABLE).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createMessagesTable(PDO $pdo): void {
        // SQL_MESSAGE_TABLE is used in wakaba.pl, assume it's 'messages' or define it in config.php
        $tableName = defined('SQL_TABLE_MESSAGES') ? SQL_TABLE_MESSAGES : (defined('SQL_MESSAGE_TABLE') ? SQL_MESSAGE_TABLE : 'messages');
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            touser TEXT,
            fromuser TEXT,
            message TEXT,
            num {$autoIncrement},
            parent INTEGER,
            timestamp INTEGER,
            lasthit INTEGER,
            wasread TINYINT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the ban requests table (SQL_TABLE_BANREQUEST - from wakaba.pl SQL_BANREQUEST_TABLE).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createBanRequestsTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_BAN_REQUESTS') ? SQL_TABLE_BAN_REQUESTS : (defined('SQL_BANREQUEST_TABLE') ? SQL_BANREQUEST_TABLE : 'ban_requests');
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            postnum INTEGER,
            postparent INTEGER,
            board TEXT,
            ip TEXT,
            reason TEXT,
            reason2 TEXT,
            fromuser TEXT,
            timestamp INTEGER
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the deleted posts table (SQL_TABLE_DELETED - from wakaba.pl SQL_DELETED_TABLE).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createDeletedPostsTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_DELETED_POSTS') ? SQL_TABLE_DELETED_POSTS : (defined('SQL_DELETED_TABLE') ? SQL_DELETED_TABLE : 'deleted_posts');
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            indexnum {$autoIncrement},
            num INTEGER,
            parent INTEGER,
            timestamp INTEGER,
            lasthit INTEGER,
            ip TEXT,
            name TEXT,
            trip TEXT,
            email TEXT,
            subject TEXT,
            comment TEXT,
            image TEXT,
            size INTEGER,
            md5 TEXT,
            width INTEGER,
            height INTEGER,
            filename TEXT,
            deletedby TEXT,
            deletedtime INTEGER,
            board TEXT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the staff log table (SQL_TABLE_LOG - from wakaba.pl SQL_LOG_TABLE).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createLogTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_LOG') ? SQL_TABLE_LOG : (defined('SQL_LOG_TABLE') ? SQL_LOG_TABLE : 'log');
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            user TEXT,
            action TEXT,
            object TEXT,
            board TEXT,
            time INTEGER,
            ip TEXT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the pass table (SQL_TABLE_PASS - from wakaba.pl SQL_PASS_TABLE).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createPassTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_PASS') ? SQL_TABLE_PASS : (defined('SQL_PASS_TABLE') ? SQL_PASS_TABLE : 'pass');
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            token TEXT,
            pin INTEGER,
            ip TEXT,
            lasthit INTEGER,
            lastswitch INTEGER,
            timestamp INTEGER,
            email TEXT,
            approved TINYINT,
            banned TINYINT
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the session table (SQL_TABLE_SESSION - from wakaba.pl SQL_SESSION_TABLE).
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createSessionTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_SESSIONS') ? SQL_TABLE_SESSIONS : (defined('SQL_SESSION_TABLE') ? SQL_SESSION_TABLE : 'sessions');
        $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            sessionkey TEXT,
            parent INTEGER, -- Assuming this refers to a user ID or similar linkage
            ip TEXT,
            ua TEXT, -- User Agent
            timestamp TEXT -- Or INTEGER if storing Unix timestamp
        )";
        $pdo->exec($sql);
    }

    /**
     * Initializes the captcha table (SQL_TABLE_CAPTCHA).
     * This is based on the constant in config.pl, actual schema might vary
     * or might not be needed if using a service like reCAPTCHA v2/v3.
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createCaptchaTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_CAPTCHA') ? SQL_TABLE_CAPTCHA : 'captcha';
        // Captcha tables often don't need an auto-incrementing ID in the same way,
        // they might use the captcha challenge string as a primary key or part of it.
        // This is a generic example.
        // $autoIncrement = self::getSqlAutoincrement();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            challenge TEXT PRIMARY KEY, -- Example: the captcha text or a unique ID
            ip_address TEXT,
            timestamp INTEGER,
            expires_at INTEGER
            -- Add other fields as needed by your chosen captcha implementation
        )";
        // Wakaba's built-in captcha uses: num (autoinc), timestamp, ip, string, used (tinyint)
        // Recreating that:
        $autoIncrement = self::getSqlAutoincrement();
        $sql_wakaba_captcha = "CREATE TABLE IF NOT EXISTS {$tableName} (
            num {$autoIncrement},
            timestamp INTEGER,
            ip TEXT,
            string TEXT,
            used TINYINT DEFAULT 0
        )";
        $pdo->exec($sql_wakaba_captcha);
    }

    /**
     * Creates all defined tables if they don't exist.
     * @param PDO $pdo The PDO connection instance.
     */
    public static function initializeAllTables(PDO $pdo): void {
        self::createPostsTable($pdo);
        self::createAdminTable($pdo);
        self::createUsersTable($pdo);
        self::createProxyTable($pdo);
        self::createReportsTable($pdo);
        self::createMessagesTable($pdo); // Uses SQL_MESSAGE_TABLE or 'messages'
        self::createBanRequestsTable($pdo); // Uses SQL_BANREQUEST_TABLE or 'ban_requests'
        self::createDeletedPostsTable($pdo); // Uses SQL_DELETED_TABLE or 'deleted_posts'
        self::createLogTable($pdo); // Uses SQL_LOG_TABLE or 'log'
        self::createPassTable($pdo); // Uses SQL_PASS_TABLE or 'pass'
        self::createSessionTable($pdo); // Uses SQL_SESSION_TABLE or 'sessions'

        // Only create captcha table if ENABLE_CAPTCHA is 'captcha' (built-in) or 'builtin'
        if (defined('ENABLE_CAPTCHA') && (ENABLE_CAPTCHA === 'captcha' || ENABLE_CAPTCHA === 'builtin')) {
            self::createActualCaptchaTable($pdo); // Renamed to avoid conflict with previous placeholder
        }
    }

    /**
     * Initializes the actual CAPTCHA table (SQL_TABLE_CAPTCHA).
     * Schema: ip TEXT, pagekey TEXT, word TEXT, timestamp INTEGER
     * @param PDO $pdo The PDO connection instance.
     * @throws PDOException on failure.
     */
    public static function createActualCaptchaTable(PDO $pdo): void {
        $tableName = defined('SQL_TABLE_CAPTCHA') ? SQL_TABLE_CAPTCHA : 'captcha';
        // No auto-increment ID needed for this table as per original schema.
        // Wakaba's captcha.pl uses: ip, pagekey, word, timestamp.

        // Drop if exists, to ensure clean state if schema changes during dev
        // In production, you might use ALTER TABLE or migrations.
        // $pdo->exec("DROP TABLE IF EXISTS {$tableName}");


        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            ip TEXT NOT NULL,
            pagekey TEXT NOT NULL,
            word TEXT NOT NULL,
            timestamp INTEGER NOT NULL,
            PRIMARY KEY (ip, pagekey) -- Ensures one CAPTCHA per IP per page context. Original had no PK.
        )";

        // Add indexes for performance
        // $sql_index_time = "CREATE INDEX IF NOT EXISTS idx_{$tableName}_timestamp ON {$tableName} (timestamp)";
        // $sql_index_lookup = "CREATE INDEX IF NOT EXISTS idx_{$tableName}_lookup ON {$tableName} (ip, pagekey, word)";


        if (defined('SQL_DBTYPE') && SQL_DBTYPE === 'sqlite') {
            // SQLite does not support PRIMARY KEY (ip, pagekey) in quite the same way for TEXT if not using WITHOUT ROWID
            // A UNIQUE constraint is better for SQLite if we want to keep the implicit rowid.
            // Or, define it as PRIMARY KEY (ip, pagekey) WITHOUT ROWID.
            // For simplicity, a named primary key on multiple columns is standard.
            // SQLite may also complain about TEXT NOT NULL without a default if strict mode is on.
            // Let's adjust slightly for broader compatibility / typical SQLite usage.
             $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
                ip TEXT,
                pagekey TEXT,
                word TEXT,
                timestamp INTEGER
            );
            CREATE INDEX IF NOT EXISTS idx_{$tableName}_lookup ON {$tableName} (ip, pagekey);
            CREATE INDEX IF NOT EXISTS idx_{$tableName}_timestamp ON {$tableName} (timestamp);";
            // For SQLite, to enforce uniqueness similar to a composite PK:
            // CREATE UNIQUE INDEX IF NOT EXISTS uidx_{$tableName}_ip_pagekey ON {$tableName} (ip, pagekey);
            // This is often handled by application logic (delete before insert).
        } else {
            // For MySQL/PostgreSQL, the composite PRIMARY KEY is fine.
            // $sql .= "; " . $sql_index_time . "; " . $sql_index_lookup;
        }

        // Execute statements one by one if they are multiple and separated by ;
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $trimmed_statement = trim($statement);
            if (!empty($trimmed_statement)) {
                $pdo->exec($trimmed_statement);
            }
        }
    }
}
