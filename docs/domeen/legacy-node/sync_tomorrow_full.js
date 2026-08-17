require('dotenv').config();
const https = require('https');
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
    
    console.log('Tõmban homme TÄIELIK päev (29.jaan 00:00-23:59 EET)...');
    
    // Homme Eesti ajas: 29.jaan 00:00 EET = 28.jaan 22:00 UTC
    //                    29.jaan 23:59 EET = 28.jaan 21:59 UTC
    // Aga see on vale! 29.jaan 23:59 EET = 29.jaan 21:59 UTC!
    const url = 'https://dashboard.elering.ee/api/nps/price?start=2026-01-28T22:00:00Z&end=2026-01-29T21:59:59Z';
    const response = await new Promise((resolve, reject) => {
        https.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });
    
    const ee = response.data.ee;
    console.log('Sain ' + ee.length + ' kirjet');
    
    // Näita esimene ja viimane
    if (ee.length > 0) {
        const first = new Date(ee[0].timestamp * 1000).toLocaleString('et-EE', {timeZone: 'Europe/Tallinn'});
        const last = new Date(ee[ee.length - 1].timestamp * 1000).toLocaleString('et-EE', {timeZone: 'Europe/Tallinn'});
        console.log('Esimene:', first);
        console.log('Viimane:', last);
    }
    
    let inserted = 0;
    for (const item of ee) {
        const start = new Date(item.timestamp * 1000);
        const end = new Date((item.timestamp + 900) * 1000);
        
        try {
            await conn.execute(
                'INSERT IGNORE INTO market_price (zone_code, period_start_utc, period_end_utc, price_eur_mwh) VALUES (?, ?, ?, ?)',
                ['EE', start, end, item.price]
            );
            inserted++;
        } catch (e) {
            console.log('Error: ' + e.message);
        }
    }
    
    console.log('Lisatud: ' + inserted, '(duplikaadid ignoreeritud)');
    await conn.end();
}

main().catch(console.error);
