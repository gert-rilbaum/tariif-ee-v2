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
    
    console.log('Tõmban täna 28.jaan kõik andmed (kaasa arvatud 22:00-23:00 EET)...');
    
    // Päringu UTC ajaga - hõlmab kogu 28.jaanuari UTC + 2h
    const url = 'https://dashboard.elering.ee/api/nps/price?start=2026-01-27T22:00:00Z&end=2026-01-29T01:59:59Z';
    const response = await new Promise((resolve, reject) => {
        https.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });
    
    const ee = response.data.ee;
    console.log('Sain ' + ee.length + ' kirjet');
    
    // EI FILTRE - lisa kõik
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
    
    console.log('Lisatud: ' + inserted);
    await conn.end();
}

main().catch(console.error);
