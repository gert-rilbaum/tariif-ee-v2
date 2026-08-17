#!/usr/bin/env node
/**
 * Elering Nord Pool tunnihindade import-skript
 *
 * Impordib elektri tunnihinnad Elering API-st market_price tabelisse.
 *
 * Kasutamine:
 *   node src/scripts/import_elering_prices.js 2025-01-01 2025-01-31
 *   node src/scripts/import_elering_prices.js 2025-01-01 2025-01-31 EE
 *   node src/scripts/import_elering_prices.js --days=30
 *   node src/scripts/import_elering_prices.js --days=7 --zone=EE
 *
 * Argumendid:
 *   from-date    Alguskuupäev (YYYY-MM-DD, Eesti aeg)
 *   to-date      Lõpukuupäev (YYYY-MM-DD, Eesti aeg, kaasav)
 *   zone         Tsoon (vaikimisi: EE)
 *   --days=N     Viimased N päeva (alternatiiv kuupäevadele)
 *
 * Näited:
 *   # Konkreetne periood
 *   node src/scripts/import_elering_prices.js 2025-01-01 2025-12-31
 *
 *   # Viimased 30 päeva
 *   node src/scripts/import_elering_prices.js --days=30
 *
 *   # Viimased 7 päeva Läti tsoonile
 *   node src/scripts/import_elering_prices.js --days=7 --zone=LV
 */

require('dotenv').config();

const https = require('https');
const { query, batchQuery, closePool } = require('../config/database');

// ============================================================
// KONFIGURATSIOON
// ============================================================

const CONFIG = {
    // Elering API
    ELERING_API_BASE: 'https://dashboard.elering.ee/api',

    // Maksimum päevi ühe päringu kohta (Elering piirang)
    MAX_DAYS_PER_REQUEST: 31,

    // Viivitus päringute vahel (ms)
    REQUEST_DELAY_MS: 1000,

    // Retry seaded
    MAX_RETRIES: 3,
    RETRY_BASE_DELAY_MS: 1000, // Exponential backoff: 1s, 2s, 4s

    // Vaikimisi tsoon
    DEFAULT_ZONE: 'EE'
};

// ============================================================
// UTILIIDID
// ============================================================

/**
 * Sleep funktsioon
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Parsi käsurea argumendid
 */
function parseArgs() {
    const args = process.argv.slice(2);
    const result = {
        fromDate: null,
        toDate: null,
        zone: CONFIG.DEFAULT_ZONE,
        days: null
    };

    for (const arg of args) {
        if (arg.startsWith('--days=')) {
            result.days = parseInt(arg.split('=')[1], 10);
        } else if (arg.startsWith('--zone=')) {
            result.zone = arg.split('=')[1].toUpperCase();
        } else if (!result.fromDate) {
            result.fromDate = arg;
        } else if (!result.toDate) {
            result.toDate = arg;
        } else if (!arg.startsWith('--')) {
            result.zone = arg.toUpperCase();
        }
    }

    // Kui --days on määratud, arvuta kuupäevad
    if (result.days) {
        const now = new Date();
        result.toDate = formatDate(now);
        const from = new Date(now);
        from.setDate(from.getDate() - result.days + 1);
        result.fromDate = formatDate(from);
    }

    return result;
}

/**
 * Formaadi kuupäev YYYY-MM-DD kujule
 */
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

/**
 * Parsi kuupäev stringist
 */
function parseDate(dateStr) {
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day);
}

/**
 * Jaga periood tükkideks (max 31 päeva)
 */
function splitPeriodIntoChunks(fromDate, toDate, maxDays = CONFIG.MAX_DAYS_PER_REQUEST) {
    const chunks = [];
    let currentStart = new Date(fromDate);
    const end = new Date(toDate);

    while (currentStart <= end) {
        const chunkEnd = new Date(currentStart);
        chunkEnd.setDate(chunkEnd.getDate() + maxDays - 1);

        if (chunkEnd > end) {
            chunkEnd.setTime(end.getTime());
        }

        chunks.push({
            from: formatDate(currentStart),
            to: formatDate(chunkEnd)
        });

        currentStart = new Date(chunkEnd);
        currentStart.setDate(currentStart.getDate() + 1);
    }

    return chunks;
}

// ============================================================
// ELERING API
// ============================================================

/**
 * HTTP GET päring Promise'ina
 */
function httpGet(url) {
    return new Promise((resolve, reject) => {
        const req = https.get(url, {
            headers: {
                'Accept': 'application/json',
                'User-Agent': 'tariif.ee/import/1.0'
            }
        }, (res) => {
            let data = '';

            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                if (res.statusCode >= 200 && res.statusCode < 300) {
                    try {
                        resolve(JSON.parse(data));
                    } catch (e) {
                        reject(new Error(`Invalid JSON response: ${e.message}`));
                    }
                } else {
                    reject(new Error(`HTTP ${res.statusCode}: ${data.substring(0, 200)}`));
                }
            });
        });

        req.on('error', reject);
        req.setTimeout(30000, () => {
            req.destroy();
            reject(new Error('Request timeout (30s)'));
        });
    });
}

/**
 * Päri hinnad Elering API-st ühe tüki jaoks
 * Retry loogikaga
 */
async function fetchEleringPricesChunk(fromDate, toDate, zone) {
    // Konverteeri Eesti kuupäevad UTC ISO stringideks
    // Eesti on UTC+2 (talvel) või UTC+3 (suvel)
    // Kasutame lihtsustatud lähenemist: eeldame UTC+2

    const startDT = new Date(`${fromDate}T00:00:00+02:00`);
    const endDT = new Date(`${toDate}T23:59:59+02:00`);

    const start = startDT.toISOString();
    const end = endDT.toISOString();

    const url = `${CONFIG.ELERING_API_BASE}/nps/price?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;

    let lastError;
    for (let attempt = 1; attempt <= CONFIG.MAX_RETRIES; attempt++) {
        try {
            const response = await httpGet(url);

            if (!response.success) {
                throw new Error(`Elering API error: success=false`);
            }

            if (!response.data || !response.data[zone.toLowerCase()]) {
                // Andmeid pole - see võib olla normaalne (tuleviku kuupäevad)
                return [];
            }

            // Teisenda andmed meie formaati
            const prices = response.data[zone.toLowerCase()].map(item => ({
                zone_code: zone.toUpperCase(),
                period_start_utc: new Date(item.timestamp * 1000),
                period_end_utc: new Date((item.timestamp + 3600) * 1000), // +1 tund
                price_eur_mwh: item.price
            }));

            return prices;

        } catch (error) {
            lastError = error;
            console.error(`   ⚠️  Katse ${attempt}/${CONFIG.MAX_RETRIES} ebaõnnestus: ${error.message}`);

            if (attempt < CONFIG.MAX_RETRIES) {
                const delay = CONFIG.RETRY_BASE_DELAY_MS * Math.pow(2, attempt - 1);
                console.log(`   ⏳ Ootan ${delay}ms enne järgmist katset...`);
                await sleep(delay);
            }
        }
    }

    throw new Error(`Elering API päring ebaõnnestus pärast ${CONFIG.MAX_RETRIES} katset: ${lastError.message}`);
}

// ============================================================
// ANDMEBAAS
// ============================================================

/**
 * Salvesta hinnad andmebaasi (batch insert)
 * Kasutab INSERT IGNORE, et vältida duplikaate
 */
async function savePricesToDb(prices) {
    if (prices.length === 0) {
        return 0;
    }

    // Kasutame INSERT IGNORE, et duplikaadid ei tekitaks vigu
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

    // mysql2 pool.query() toetab VALUES ? batch inserti
    const [result] = await batchQuery(sql, [values]);

    return result.affectedRows;
}

/**
 * Kontrolli, kas tabel eksisteerib
 */
async function checkTableExists() {
    try {
        await query('SELECT 1 FROM market_price LIMIT 1');
        return true;
    } catch (error) {
        if (error.code === 'ER_NO_SUCH_TABLE') {
            return false;
        }
        throw error;
    }
}

// ============================================================
// PEAFUNKTSIOON
// ============================================================

async function main() {
    console.log('\n╔════════════════════════════════════════════════════════╗');
    console.log('║  Elering Nord Pool hindade import                      ║');
    console.log('║  tariif.ee                                             ║');
    console.log('╚════════════════════════════════════════════════════════╝\n');

    // Parsi argumendid
    const args = parseArgs();

    if (!args.fromDate || !args.toDate) {
        console.error('❌ Kuupäevad on kohustuslikud!\n');
        console.log('Kasutamine:');
        console.log('  node src/scripts/import_elering_prices.js 2025-01-01 2025-01-31');
        console.log('  node src/scripts/import_elering_prices.js --days=30');
        console.log('  node src/scripts/import_elering_prices.js --days=7 --zone=EE\n');
        process.exit(1);
    }

    const fromDate = parseDate(args.fromDate);
    const toDate = parseDate(args.toDate);

    if (fromDate > toDate) {
        console.error('❌ Alguskuupäev ei saa olla hilisem kui lõpukuupäev!');
        process.exit(1);
    }

    console.log(`📅 Periood: ${args.fromDate} kuni ${args.toDate}`);
    console.log(`🌍 Tsoon: ${args.zone}`);

    // Kontrolli, kas tabel eksisteerib
    const tableExists = await checkTableExists();
    if (!tableExists) {
        console.error('\n❌ Tabel market_price ei eksisteeri!');
        console.log('   Käivita kõigepealt migratsioon:');
        console.log('   node src/sql/run-migration.js 2025_12_add_tariff_core\n');
        await closePool();
        process.exit(1);
    }

    // Jaga periood tükkideks
    const chunks = splitPeriodIntoChunks(fromDate, toDate);
    console.log(`📦 Tükke: ${chunks.length} (max ${CONFIG.MAX_DAYS_PER_REQUEST} päeva tükis)\n`);

    let totalImported = 0;
    let totalSkipped = 0;

    for (let i = 0; i < chunks.length; i++) {
        const chunk = chunks[i];

        try {
            // Päri andmed
            const prices = await fetchEleringPricesChunk(chunk.from, chunk.to, args.zone);

            if (prices.length === 0) {
                console.log(`[Chunk ${i + 1}] ${chunk.from} → ${chunk.to}: API 0h (andmeid pole)`);
                continue;
            }

            // Salvesta andmebaasi
            const inserted = await savePricesToDb(prices);
            const skipped = prices.length - inserted;
            totalImported += inserted;
            totalSkipped += skipped;

            // Kompaktne ühe rea logi
            console.log(`[Chunk ${i + 1}] ${chunk.from} → ${chunk.to}: API ${prices.length}h, lisatud ${inserted}, ignoreeritud ${skipped}`);

        } catch (error) {
            console.error(`\n❌ Viga tüki ${i + 1} töötlemisel: ${error.message}`);
            await closePool();
            process.exit(1);
        }

        // Viivitus järgmise päringu ees (kui pole viimane)
        if (i < chunks.length - 1) {
            await sleep(CONFIG.REQUEST_DELAY_MS);
        }
    }

    // Kokkuvõte
    console.log('\n╔════════════════════════════════════════════════════════╗');
    console.log('║  Import lõpetatud                                      ║');
    console.log('╠════════════════════════════════════════════════════════╣');
    console.log(`║  Uusi ridu:      ${String(totalImported).padStart(6)} tundi                       ║`);
    console.log(`║  Juba olemas:    ${String(totalSkipped).padStart(6)} tundi                       ║`);
    console.log(`║  Kokku tükke:    ${String(chunks.length).padStart(6)}                             ║`);
    console.log('╚════════════════════════════════════════════════════════╝\n');

    await closePool();
    process.exit(0);
}

// Käivita
main().catch(async (error) => {
    console.error('\n❌ Ootamatu viga:', error.message);
    console.error(error.stack);
    await closePool();
    process.exit(1);
});
