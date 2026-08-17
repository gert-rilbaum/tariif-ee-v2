require('dotenv').config();
const mysql = require('mysql2/promise');

async function main() {
    const conn = await mysql.createConnection({
        host: 'd143516.mysql.zonevs.eu',
        port: 3306,
        user: 'd143516sa565559',
        password: process.env.DB_PASSWORD /* EEMALDATUD repos hoidmisel */,
        database: 'd143516sd619698',
        timezone: 'Z'
    });
    
    console.log('Kustutan homseid kirjeid (29.jaan 00:00-01:45 EET = 28.jaan 22:00-23:45 UTC)...');
    
    // Kustuta kõik 28.jaan pärast 22:00 UTC (see on 29.jaan EET)
    const result = await conn.execute(
        "DELETE FROM market_price WHERE zone_code = 'EE' AND period_start_utc >= '2026-01-28 22:00:00' AND period_start_utc < '2026-01-29 22:00:00'"
    );
    
    console.log('Kustutatud:', result[0].affectedRows, 'kirjet');
    await conn.end();
}

main().catch(console.error);
