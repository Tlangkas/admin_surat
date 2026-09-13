<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterTemplate;
use Database\Seeders\LetterTemplate2025Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTemplate2025SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_core_school_templates(): void
    {
        $this->seed(LetterTemplate2025Seeder::class);

        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'DISPEN']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'SP-ORTU']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'SP-SISWA']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'SP-GUKAR']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'SHV']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'ST-GUKAR']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'SPPD']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'REKOM']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'SK-AKTIF']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'SK-PINDAH']);
        $this->assertDatabaseHas('letter_templates', ['letter_code' => 'UND']);

        $count = LetterTemplate::count();
        $this->assertGreaterThanOrEqual(11, $count);
    }

    public function test_seeder_is_idempotent_and_does_not_duplicate(): void
    {
        $this->seed(LetterTemplate2025Seeder::class);
        $countAfterFirst = LetterTemplate::count();

        // Run second time
        $this->seed(LetterTemplate2025Seeder::class);
        $countAfterSecond = LetterTemplate::count();

        $this->assertSame($countAfterFirst, $countAfterSecond);
    }
}
