#!/usr/bin/env node
/**
 * Elering 2-aasta hindade import - KONSERVATIIVNE VERSIOON
 *
 * Suurte ajalooliste andmemahtude jaoks, mis:
 * - Kasutab pikemat viivitust päringute vahel (3 sekundit)
 * - Teeb pause iga 10 tüki järel (lisaks 15 sekundit)
 * - Näitab progressi protsentides
 * - Salvestab checkpoint-faili (jätkamine pärast katkestust)
 *
 * Kasutamine:
 *   node src/scripts/import_elering_2years.js
 *   node src/scripts/import_elering_2years.js --dry-run
 *   node src/scripts/import_elering_2years.js --resume
 *   node src/scripts/import_elering_2years.js --from=2023-01-01 --to=2024-12-31
 */

require('dotenv').config();

const https = require('https');
const fs = require('fs');
const path = require('path');
const { query, batchQuery, closePool } = require('../config/database');

// ============================================================
// KONFIGURATSIOON - KONSERVATIIVNE
// ============================================================

const CONFIG = {
    // Elering API
    ELERING_API_BASE: 'https://dashboard.elering.ee/api',

    // Maksimum päevi ühe päringu kohta (Elering piirang)
    MAX_DAYS_PER_REQUEST: 31,

    // VÄGA KONSERVATIIVNE: 2 minutit päringute vahel (~1h kogu import)
    REQUEST_DELAY_MS: 120000, // 2 minutit

    // VÄGA KONSERVATIIVNE: Lisa paus iga N tüki järel
    PAUSE_EVERY_N_CHUNKS: 5,
    PAUSE_DURATION_MS: 60000, // 1 minut

    // Retry seaded
    MAX_RETRIES: 5,  // Rohkem katseid
    RETRY_BASE_DELAY_MS: 2000, // 2s, 4s, 8s, 16s, 32s

    // Vaikimisi tsoon
    DEFAULT_ZONE: 'EE',

    // Checkpoint fail
    CHECKPOINT_FILE: path.join(__dirname, '../../.elering_checkpoint.json')
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

function parseDate(dateStr) {
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function formatDuration(ms) {
    const seconds = Math.floor(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    if (hours > 0) {
        return `${hours}h ${minutes % 60}m`;
    } else if (minutes > 0) {
        return `${minutes}m ${seconds % 60}s`;
    }
    return `${seconds}s`;
}

function parseArgs() {
    const args = process.argv.slice(2);
    const result = {
        fromDate: null,
        toDate: null,
        zone: CONFIG.DEFAULT_ZONE,
        dryRun: false,
        resume: false
    };

    for (const arg of args) {
        if (arg === '--dry-run') {
            result.dryRun = true;
        } else if (arg === '--resume') {
            result.resume = true;
        } else if (arg.startsWith('--from=')) {
            result.fromDate = arg.split('=')[1];
        } else if (arg.startsWith('--to=')) {
            result.toDate = arg.split('=')[1];
        } else if (arg.startsWith('--zone=')) {
            result.zone = arg.split('=')[1].toUpperCase();
        }
    }

    // Vaikimisi: viimased 2 aastat
    if (!result.fromDate || !result.toDate) {
        const now = new Date();
        result.toDate = formatDate(now);
        const twoYearsAgo = new Date(now);
        twoYearsAgo.setFullYear(twoYearsAgo.getFullYear() - 2);
        result.fromDate = formatDate(twoYearsAgo);
    }

    return result;
}

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
// CHECKPOINT
// ============================================================

function loadCheckpoint() {
    try {
        if (fs.existsSync(CONFIG.CHECKPOINT_FILE)) {
            const data = JSON.parse(fs.readFileSync(CONFIG.CHECKPOINT_FILE, 'utf8'));
            return data;
        }
    } catch (e) {
        console.log('⚠️  Checkpoint faili lugemisel tekkis viga, alustan algusest');
    }
    return null;
}

function saveCheckpoint(data) {
    try {
        fs.writeFileSync(CONFIG.CHECKPOINT_FILE, JSON.stringify(data, null, 2));
    } catch (e) {
        console.error('⚠️  Checkpoint salvestamine ebaõnnestus:', e.message);
    }
}

function clearCheckpoint() {
    try {
        if (fs.existsSync(CONFIG.CHECKPOINT_FILE)) {
            fs.unlinkSync(CONFIG.CHECKPOINT_FILE);
        }
    } catch (e) {
        // Ignore
    }
}

// ============================================================
// ELERING API
// ============================================================

function httpGet(url) {
    return new Promise((resolve, reject) => {
        const req = https.get(url, {
            headers: {
                'Accept': 'application/json',
                'User-Agent': 'tariif.ee/bulk-import/1.0 (contact: gert@rilbaum.ee)'
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
                } else if (res.statusCode === 429) {
                    reject(new Error('RATE_LIMITED'));
                } else {
                    reject(new Error(`HTTP ${res.statusCode}`));
                }
            });
        });

        req.on('error', reject);
        req.setTimeout(60000, () => {
            req.destroy();
            reject(new Error('Request timeout (60s)'));
        });
    });
}

async function fetchEleringPricesChunk(fromDate, toDate, zone) {
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
                throw new Error('API success=false');
            }

            if (!response.data || !response.data[zone.toLowerCase()]) {
                return [];
            }

            const rawData = response.data[zone.toLowerCase()];

            // Tuvasta intervall automaatselt (15 min või 1h)
            let intervalSec = 3600; // vaikimisi 1h
            if (rawData.length >= 2) {
                const diff = rawData[1].timestamp - rawData[0].timestamp;
                if (diff === 900) {
                    intervalSec = 900; // 15 min
                }
            }

            const prices = rawData.map(item => ({
                zone_code: zone.toUpperCase(),
                period_start_utc: new Date(item.timestamp * 1000),
                period_end_utc: new Date((item.timestamp + intervalSec) * 1000),
                price_eur_mwh: item.price
            }));

            return prices;

        } catch (error) {
            lastError = error;

            // Rate limit - oota kauem
            if (error.message === 'RATE_LIMITED') {
                const waitTime = 60000 * attempt; // 1 min, 2 min, 3 min...
                console.log(`   ⚠️  Rate limited! Ootan ${waitTime/1000}s...`);
                await sleep(waitTime);
                continue;
            }

            console.log(`   ⚠️  Katse ${attempt}/${CONFIG.MAX_RETRIES}: ${error.message}`);

            if (attempt < CONFIG.MAX_RETRIES) {
                const delay = CONFIG.RETRY_BASE_DELAY_MS * Math.pow(2, attempt - 1);
                console.log(`   ⏳ Ootan ${delay/1000}s...`);
                await sleep(delay);
            }
        }
    }

    throw new Error(`Ebaõnnestus: ${lastError.message}`);
}

// ============================================================
// ANDMEBAAS
// ============================================================

async function savePricesToDb(prices) {
    if (prices.length === 0) return 0;

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
    return result.affectedRows;
}

async function checkTableExists() {
    try {
        await query('SELECT 1 FROM market_price LIMIT 1');
        return true;
    } catch (error) {
        if (error.code === 'ER_NO_SUCH_TABLE') return false;
        throw error;
    }
}

async function getExistingRecordCount() {
    const [rows] = await query('SELECT COUNT(*) as cnt FROM market_price');
    return rows[0].cnt;
}

// ============================================================
// PROGRESS BAR
// ============================================================

function drawProgressBar(current, total, width = 30) {
    const percent = Math.round((current / total) * 100);
    const filled = Math.round((current / total) * width);
    const empty = width - filled;
    const bar = '█'.repeat(filled) + '░'.repeat(empty);
    return `[${bar}] ${percent}%`;
}

// ============================================================
// MAIN
// ============================================================

async function main() {
    console.log('\n╔══════════════════════════════════════════════════════════════╗');
    console.log('║  Elering 2-aasta import - KONSERVATIIVNE VERSIOON            ║');
    console.log('║  tariif.ee                                                   ║');
    console.log('╚══════════════════════════════════════════════════════════════╝\n');

    const args = parseArgs();

    console.log('⚙️  Seaded:');
    console.log(`   • Viivitus päringute vahel: ${CONFIG.REQUEST_DELAY_MS / 1000}s`);
    console.log(`   • Paus iga ${CONFIG.PAUSE_EVERY_N_CHUNKS} tüki järel: ${CONFIG.PAUSE_DURATION_MS / 1000}s`);
    console.log(`   • Max retries: ${CONFIG.MAX_RETRIES}`);
    console.log(`   • Dry-run: ${args.dryRun ? 'JAH' : 'EI'}`);
    console.log('');

    console.log(`📅 Periood: ${args.fromDate} → ${args.toDate}`);
    console.log(`🌍 Tsoon: ${args.zone}`);

    // Kontrolli tabelit
    if (!args.dryRun) {
        const tableExists = await checkTableExists();
        if (!tableExists) {
            console.error('\n❌ Tabel market_price ei eksisteeri!');
            console.log('   Käivita: npm run db:migrate:tariff\n');
            await closePool();
            process.exit(1);
        }

        const existingCount = await getExistingRecordCount();
        console.log(`📊 Olemasolevaid kirjeid: ${existingCount.toLocaleString()}`);
    }

    // Tükid
    const fromDate = parseDate(args.fromDate);
    const toDate = parseDate(args.toDate);
    const chunks = splitPeriodIntoChunks(fromDate, toDate);

    console.log(`📦 Tükke kokku: ${chunks.length}`);

    // Arvuta hinnanguline aeg
    const estimatedTime = chunks.length * CONFIG.REQUEST_DELAY_MS
        + Math.floor(chunks.length / CONFIG.PAUSE_EVERY_N_CHUNKS) * CONFIG.PAUSE_DURATION_MS;
    console.log(`⏱️  Hinnanguline aeg: ${formatDuration(estimatedTime)}`);
    console.log('');

    // Resume kontroll
    let startIndex = 0;
    if (args.resume) {
        const checkpoint = loadCheckpoint();
        if (checkpoint && checkpoint.fromDate === args.fromDate && checkpoint.toDate === args.toDate) {
            startIndex = checkpoint.lastCompletedIndex + 1;
            console.log(`🔄 Jätkan checkpoint-ist: tükk ${startIndex + 1}/${chunks.length}`);
        }
    }

    if (args.dryRun) {
        console.log('🧪 DRY-RUN režiim - andmebaasi ei kirjutata\n');
        console.log('Tükid, mis tõmmataks:');
        for (let i = 0; i < Math.min(5, chunks.length); i++) {
            console.log(`   ${i + 1}. ${chunks[i].from} → ${chunks[i].to}`);
        }
        if (chunks.length > 5) {
            console.log(`   ... ja veel ${chunks.length - 5} tükki`);
        }
        console.log('\nKäivita ilma --dry-run võtmeta, et alustada importi.');
        await closePool();
        process.exit(0);
    }

    // Import
    console.log('🚀 Alustan importi...\n');
    const startTime = Date.now();

    let totalImported = 0;
    let totalSkipped = 0;
    let totalHours = 0;

    for (let i = startIndex; i < chunks.length; i++) {
        const chunk = chunks[i];
        const progress = drawProgressBar(i + 1, chunks.length);

        try {
            const prices = await fetchEleringPricesChunk(chunk.from, chunk.to, args.zone);
            totalHours += prices.length;

            if (prices.length === 0) {
                process.stdout.write(`\r${progress} [${i + 1}/${chunks.length}] ${chunk.from} → ${chunk.to}: pole andmeid        `);
            } else {
                const inserted = await savePricesToDb(prices);
                const skipped = prices.length - inserted;
                totalImported += inserted;
                totalSkipped += skipped;

                process.stdout.write(`\r${progress} [${i + 1}/${chunks.length}] ${chunk.from} → ${chunk.to}: +${inserted} uut, ${skipped} olemas    `);
            }

            // Salvesta checkpoint
            saveCheckpoint({
                fromDate: args.fromDate,
                toDate: args.toDate,
                zone: args.zone,
                lastCompletedIndex: i,
                totalImported,
                totalSkipped
            });

        } catch (error) {
            console.error(`\n\n❌ Viga tükil ${i + 1}: ${error.message}`);
            console.log('   Checkpoint salvestatud. Jätka: node src/scripts/import_elering_2years.js --resume\n');
            await closePool();
            process.exit(1);
        }

        // Viivitus
        if (i < chunks.length - 1) {
            await sleep(CONFIG.REQUEST_DELAY_MS);

            // Lisa paus iga N tüki järel
            if ((i + 1) % CONFIG.PAUSE_EVERY_N_CHUNKS === 0) {
                console.log(`\n⏸️  Paus ${CONFIG.PAUSE_DURATION_MS / 1000}s (tükid ${i + 1}/${chunks.length})...`);
                await sleep(CONFIG.PAUSE_DURATION_MS);
            }
        }
    }

    const elapsed = Date.now() - startTime;

    // Puhasta checkpoint
    clearCheckpoint();

    // Kokkuvõte
    console.log('\n\n╔══════════════════════════════════════════════════════════════╗');
    console.log('║  ✅ IMPORT LÕPETATUD                                         ║');
    console.log('╠══════════════════════════════════════════════════════════════╣');
    console.log(`║  Periood:        ${args.fromDate} → ${args.toDate}`.padEnd(65) + '║');
    console.log(`║  Tükke:          ${chunks.length}`.padEnd(65) + '║');
    console.log(`║  Tunde API-st:   ${totalHours.toLocaleString()}`.padEnd(65) + '║');
    console.log(`║  Uusi kirjeid:   ${totalImported.toLocaleString()}`.padEnd(65) + '║');
    console.log(`║  Juba olemas:    ${totalSkipped.toLocaleString()}`.padEnd(65) + '║');
    console.log(`║  Ajakulu:        ${formatDuration(elapsed)}`.padEnd(65) + '║');
    console.log('╚══════════════════════════════════════════════════════════════╝\n');

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
