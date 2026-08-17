#!/usr/bin/env node
/**
 * Elering igapäevane hindade import - OPTIMEERITUD
 *
 * Tõmbab viimase 3 päeva + homse hinnad.
 * Kontrollib ENNE API päringut, kas andmed on juba olemas.
 *
 * Mõeldud cron job-ina käivitamiseks 4× päevas:
 * 10,55 13,14 * * * (13:10, 13:55, 14:10, 14:55)
 *
 * (Nord Pool avaldab homse hinnad ~13:42 EET)
 *
 * Kasutamine:
 *   node src/scripts/import_elering_daily.js
 *   node src/scripts/import_elering_daily.js --days=7
 *   node src/scripts/import_elering_daily.js --force  # ignoreeri cache
 *
 * Cron näide:
 *   10,55 13,14 * * * cd /path/to/app && node src/scripts/import_elering_daily.js >> logs/elering_daily.log 2>&1
 */

require('dotenv').config();

const https = require('https');
const { query, batchQuery, closePool } = require('../config/database');

// ============================================================
// KONFIGURATSIOON
// ============================================================

const CONFIG = {
    ELERING_API_BASE: 'https://dashboard.elering.ee/api',
    DEFAULT_DAYS_BACK: 3,  // Mitu päeva tagasi + homme
    DEFAULT_ZONE: 'EE',
    REQUEST_TIMEOUT_MS: 30000,
    MAX_RETRIES: 3,
    RETRY_DELAY_MS: 5000,
    MIN_RECORDS_PER_DAY: 90  // 15min intervall = 96, 1h = 24, kasuta 90 et olla turvaline
};

// ============================================================
// UTILIIDID
// ============================================================

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function parseArgs() {
    const args = process.argv.slice(2);
    const result = {
        daysBack: CONFIG.DEFAULT_DAYS_BACK,
        zone: CONFIG.DEFAULT_ZONE,
        force: false  // --force: ignoreeri cache, tõmba alati
    };

    for (const arg of args) {
        if (arg.startsWith('--days=')) {
            result.daysBack = parseInt(arg.split('=')[1], 10);
        } else if (arg.startsWith('--zone=')) {
            result.zone = arg.split('=')[1].toUpperCase();
        } else if (arg === '--force') {
            result.force = true;
        }
    }

    return result;
}

function log(msg) {
    const now = new Date().toISOString();
    console.log(`[${now}] ${msg}`);
}

// ============================================================
// ANDMEBAASI KONTROLL
// ============================================================

/**
 * Kontrolli, millised kuupäevad on juba olemas
 * @returns {Array<string>} - Kuupäevad (YYYY-MM-DD), millel on piisavalt andmeid
 */
async function checkExistingData(from, to, zone) {
    const [rows] = await query(`
        SELECT
            DATE(period_start_utc) as date,
            COUNT(*) as record_count
        FROM market_price
        WHERE zone_code = ?
          AND DATE(period_start_utc) BETWEEN ? AND ?
        GROUP BY DATE(period_start_utc)
    `, [zone, from, to]);

    // Tagasta kuupäevad, kus on piisavalt kirjeid (>=90)
    // MySQL tagastab Date objekti, konverteeri stringiks
    return rows
        .filter(row => row.record_count >= CONFIG.MIN_RECORDS_PER_DAY)
        .map(row => {
            // Konverteeri Date objekt YYYY-MM-DD stringiks
            if (row.date instanceof Date) {
                return formatDate(row.date);
            }
            return row.date;
        });
}

/**
 * Leia puuduvad kuupäevad vahemikus
 */
function findMissingDates(from, to, existingDates) {
    const missing = [];
    const current = new Date(from);
    const end = new Date(to);

    while (current <= end) {
        const dateStr = formatDate(current);
        if (!existingDates.includes(dateStr)) {
            missing.push(dateStr);
        }
        current.setDate(current.getDate() + 1);
    }

    return missing;
}

// ============================================================
// ELERING API
// ============================================================

function httpGet(url) {
    return new Promise((resolve, reject) => {
        const req = https.get(url, {
            headers: {
                'Accept': 'application/json',
                'User-Agent': 'tariif.ee/daily-import/1.0'
            }
        }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                if (res.statusCode >= 200 && res.statusCode < 300) {
                    try {
                        resolve(JSON.parse(data));
                    } catch (e) {
                        reject(new Error(`Invalid JSON: ${e.message}`));
                    }
                } else {
                    reject(new Error(`HTTP ${res.statusCode}`));
                }
            });
        });

        req.on('error', reject);
        req.setTimeout(CONFIG.REQUEST_TIMEOUT_MS, () => {
            req.destroy();
            reject(new Error('Request timeout'));
        });
    });
}

async function fetchEleringPrices(fromDate, toDate, zone) {
    const startDT = new Date(`${fromDate}T00:00:00+02:00`);
    const endDT = new Date(`${toDate}T23:59:59+02:00`);

    const url = `${CONFIG.ELERING_API_BASE}/nps/price?start=${encodeURIComponent(startDT.toISOString())}&end=${encodeURIComponent(endDT.toISOString())}`;

    let lastError;
    for (let attempt = 1; attempt <= CONFIG.MAX_RETRIES; attempt++) {
        try {
            const response = await httpGet(url);

            if (!response.success || !response.data || !response.data[zone.toLowerCase()]) {
                return [];
            }

            const rawData = response.data[zone.toLowerCase()];

            // Tuvasta intervall automaatselt (15 min või 1h)
            let intervalSec = 3600;
            if (rawData.length >= 2) {
                const diff = rawData[1].timestamp - rawData[0].timestamp;
                if (diff === 900) {
                    intervalSec = 900;
                }
            }

            return rawData.map(item => ({
                zone_code: zone.toUpperCase(),
                period_start_utc: new Date(item.timestamp * 1000),
                period_end_utc: new Date((item.timestamp + intervalSec) * 1000),
                price_eur_mwh: item.price
            }));

        } catch (error) {
            lastError = error;
            log(`⚠️  Katse ${attempt}/${CONFIG.MAX_RETRIES}: ${error.message}`);
            if (attempt < CONFIG.MAX_RETRIES) {
                await sleep(CONFIG.RETRY_DELAY_MS);
            }
        }
    }

    throw new Error(`API päring ebaõnnestus: ${lastError.message}`);
}

// ============================================================
// ANDMEBAAS
// ============================================================

async function savePricesToDb(prices) {
    if (prices.length === 0) return { inserted: 0, skipped: 0 };

    const sql = `
        INSERT IGNORE INTO market_price
        (zone_code, period_start_utc, period_end_utc, price_eur_mwh)
        VALUES ?
    `;

    const values = prices.map(p => [
        p.zone_code,
        p.period_start_utc,
        p.period_end_utc,
        p.price_eur_mwh
    ]);

    const [result] = await batchQuery(sql, [values]);
    return {
        inserted: result.affectedRows,
        skipped: prices.length - result.affectedRows
    };
}

// ============================================================
// MAIN
// ============================================================

async function main() {
    const args = parseArgs();

    log('═══════════════════════════════════════════');
    log('Elering igapäevane hindade import (OPTIMEERITUD)');
    log('═══════════════════════════════════════════');

    // Arvuta kuupäevad: N päeva tagasi kuni homme
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const fromDate = new Date(now);
    fromDate.setDate(fromDate.getDate() - args.daysBack);

    const from = formatDate(fromDate);
    const to = formatDate(tomorrow);

    log(`Periood: ${from} → ${to}`);
    log(`Tsoon: ${args.zone}`);

    try {
        // OPTIMEERIMINE: Kontrolli, kas andmed on juba olemas
        if (!args.force) {
            log('🔍 Kontrollin olemasolevaid andmeid...');
            const existingDates = await checkExistingData(from, to, args.zone);
            const missingDates = findMissingDates(from, to, existingDates);

            log(`Olemas: ${existingDates.length} päeva, puudub: ${missingDates.length} päeva`);

            if (missingDates.length === 0) {
                log('✅ Kõik andmed juba olemas, API päringut pole vaja!');
                await closePool();
                process.exit(0);
            }

            log(`Puuduvad kuupäevad: ${missingDates.join(', ')}`);
        } else {
            log('⚠️  --force režiim: tõmban andmed ka kui olemas');
        }

        // Tõmba andmed
        log('📡 Tõmban andmeid Elering API-st...');
        const prices = await fetchEleringPrices(from, to, args.zone);
        log(`API tagastas: ${prices.length} kirjet`);

        if (prices.length === 0) {
            log('⚠️  Andmeid pole, lõpetan.');
            await closePool();
            process.exit(0);
        }

        // Salvesta
        const { inserted, skipped } = await savePricesToDb(prices);
        log(`Lisatud: ${inserted} uut, juba olemas: ${skipped}`);

        log('✅ Import lõpetatud edukalt');
        await closePool();
        process.exit(0);

    } catch (error) {
        log(`❌ Viga: ${error.message}`);
        await closePool();
        process.exit(1);
    }
}

main();
