<?php

namespace Tests\Feature\Console;

use App\Models\GridPackageVersion;
use App\Models\TariffSourceCheck;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckTariffSourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** ETag-iga vastus: sõrmejälg tuleb päistest, keha ei laadita alla. */
    private function fakePaistega(string $etag): void
    {
        Http::fake(['public-docs.elektrilevi.ee/*' => Http::response('', 200, [
            'ETag' => '"'.$etag.'"',
            'Last-Modified' => 'Mon, 01 Jun 2026 00:00:00 GMT',
            'Content-Length' => '387933',
        ])]);
    }

    public function test_esimene_kontroll_salvestab_sormejalje(): void
    {
        $this->fakePaistega('v1');

        $this->artisan('tariff:check-sources')->assertSuccessful();

        $kirje = TariffSourceCheck::first();
        $this->assertNotNull($kirje->checksum);
        $this->assertSame(387933, $kirje->size_bytes);
        $this->assertNull($kirje->changed_at);
        $this->assertTrue($kirje->acknowledged);
    }

    public function test_muutumatu_allikas_ei_tekita_teadet(): void
    {
        $this->fakePaistega('v1');

        $this->artisan('tariff:check-sources')->assertSuccessful();
        $this->artisan('tariff:check-sources')->assertSuccessful();

        $this->assertNull(TariffSourceCheck::first()->changed_at);
        $this->assertTrue(TariffSourceCheck::unacknowledgedChanges()->isEmpty());
    }

    public function test_muutunud_allikas_margitakse_ulevaatamist_vajavaks(): void
    {
        Http::fake(['public-docs.elektrilevi.ee/*' => Http::sequence()
            ->push('', 200, ['ETag' => '"v1"', 'Content-Length' => '387933'])
            ->push('', 200, ['ETag' => '"v2-uued-hinnad"', 'Content-Length' => '390000']),
        ]);

        $this->artisan('tariff:check-sources')->assertSuccessful();

        // Teine käivitus leiab muutuse → veakood teeb selle cron-logis nähtavaks
        $this->artisan('tariff:check-sources')->assertFailed();

        $kirje = TariffSourceCheck::first();
        $this->assertNotNull($kirje->changed_at);
        $this->assertFalse($kirje->acknowledged);
        $this->assertCount(1, TariffSourceCheck::unacknowledgedChanges());
    }

    public function test_paisteta_allikas_kasutab_keha_rasi(): void
    {
        // ETag ja Last-Modified puuduvad → langeme keha peale tagasi
        Http::fake(['public-docs.elektrilevi.ee/*' => Http::response('PDF-SISU-V1')]);

        $this->artisan('tariff:check-sources')->assertSuccessful();

        $this->assertSame(hash('sha256', 'PDF-SISU-V1'), TariffSourceCheck::first()->checksum);
    }

    public function test_kattesaamatus_ei_ole_krahh_aga_ei_ole_ka_edu(): void
    {
        Http::fake(['public-docs.elektrilevi.ee/*' => Http::response('', 503)]);

        // "Ei saanud kontrollida" ei tohi paista kui "muutumatu"
        $this->artisan('tariff:check-sources')->assertFailed();

        $kirje = TariffSourceCheck::first();
        $this->assertNotNull($kirje->last_error);
        $this->assertNull($kirje->checksum);
    }

    public function test_429_ei_pommita_allikat_kordustega(): void
    {
        Http::fake(['public-docs.elektrilevi.ee/*' => Http::response('', 429)]);

        $this->artisan('tariff:check-sources')->assertFailed();

        // HEAD + üks GET, mitte kordusetulv
        Http::assertSentCount(2);
        $this->assertStringContainsString('429', TariffSourceCheck::first()->last_error);
    }

    public function test_vananenud_kirjed_annavad_hoiatuse(): void
    {
        Http::fake(['public-docs.elektrilevi.ee/*' => Http::response('PDF')]);

        GridPackageVersion::query()->update(['verified_at' => CarbonImmutable::now()->subDays(200)]);

        $this->artisan('tariff:check-sources')->assertFailed();
    }

    public function test_kehtivuse_lopp_annab_hoiatuse(): void
    {
        Http::fake(['public-docs.elektrilevi.ee/*' => Http::response('PDF')]);

        GridPackageVersion::query()->update(['valid_to' => CarbonImmutable::now()->subDay()->toDateString()]);

        $this->artisan('tariff:check-sources')->assertFailed();
    }
}
