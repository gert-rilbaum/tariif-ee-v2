/**
 * Andmebaasi konfiguratsioon ja ühenduse haldus
 * MariaDB / MySQL ühendus Zone.ee serveriga
 */

const mysql = require('mysql2/promise');

// Konfiguratsioon
const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT, 10) || 3306,
    user: process.env.DB_USER || 'tariif_user',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'tariif_db',
    
    // Connection pool seaded
    waitForConnections: true,
    connectionLimit: parseInt(process.env.DB_POOL_MAX, 10) || 10,
    queueLimit: 0,
    
    // Charset
    charset: 'utf8mb4',
    
    // Timezone (Eesti aja jaoks)
    timezone: 'Z',
    
    // Turvalisus
    multipleStatements: false, // SQL injection kaitse
    
    // Debug (ainult development)
    debug: process.env.NODE_ENV === 'development' && process.env.DB_DEBUG === 'true'
};

// Loo connection pool
const pool = mysql.createPool(dbConfig);

/**
 * Ühe päringu täitmine (prepared statement)
 * @param {string} sql - SQL päring parameetritega (?)
 * @param {Array} params - Parameetrite massiiv
 * @returns {Promise<Array>} - [rows, fields]
 */
async function query(sql, params = []) {
    try {
        const [rows, fields] = await pool.execute(sql, params);
        return [rows, fields];
    } catch (error) {
        console.error('Database query error:', {
            sql: sql.substring(0, 100),
            error: error.message
        });
        throw error;
    }
}

/**
 * Batch insert päring (VALUES ? süntaks)
 * NB: pool.query() toetab batch inserti, pool.execute() ei toeta
 * @param {string} sql - SQL päring VALUES ? süntaksiga
 * @param {Array} params - [[row1], [row2], ...]
 * @returns {Promise<Array>} - [result, fields]
 */
async function batchQuery(sql, params = []) {
    try {
        const [rows, fields] = await pool.query(sql, params);
        return [rows, fields];
    } catch (error) {
        console.error('Database batch query error:', {
            sql: sql.substring(0, 100),
            error: error.message
        });
        throw error;
    }
}

/**
 * Transaktsioon mitme päringu jaoks
 * @param {Function} callback - Funktsioon, mis saab connection objekti
 * @returns {Promise<any>} - Callback tulemus
 */
async function transaction(callback) {
    const connection = await pool.getConnection();
    
    try {
        await connection.beginTransaction();
        const result = await callback(connection);
        await connection.commit();
        return result;
    } catch (error) {
        await connection.rollback();
        throw error;
    } finally {
        connection.release();
    }
}

/**
 * Kontrolli andmebaasi ühendust
 * @returns {Promise<boolean>}
 */
async function checkConnection() {
    try {
        const [rows] = await query('SELECT 1 as ok');
        return rows[0].ok === 1;
    } catch (error) {
        console.error('Database connection check failed:', error.message);
        return false;
    }
}

/**
 * Sulge pool (graceful shutdown jaoks)
 */
async function closePool() {
    await pool.end();
    console.log('Database pool closed');
}

// Ekspordi
module.exports = {
    pool,
    query,
    batchQuery,
    transaction,
    checkConnection,
    closePool
};
