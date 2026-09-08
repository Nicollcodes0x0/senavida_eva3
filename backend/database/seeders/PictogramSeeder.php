<?php

namespace Database\Seeders;

use App\Enums\PictogramSeverity;
use App\Models\Pictogram;
use App\Models\PictogramCategory;
use Illuminate\Database\Seeder;

class PictogramSeeder extends Seeder
{
    public function run(): void
    {
        $dolor = PictogramCategory::create(['name' => 'Dolor', 'sort_order' => 1]);
        $sintomas = PictogramCategory::create(['name' => 'Síntomas', 'sort_order' => 2]);
        $necesidades = PictogramCategory::create(['name' => 'Necesidades', 'sort_order' => 3]);
        $respuestas = PictogramCategory::create(['name' => 'Respuestas', 'sort_order' => 4]);

        $pictograms = [
            [$dolor, 'Dolor de cabeza', 'Me duele la cabeza', 'Tengo dolor de cabeza', '🤕', PictogramSeverity::Warning],
            [$dolor, 'Dolor de pecho', 'Me duele el pecho', 'Tengo dolor en el pecho', '💔', PictogramSeverity::Critical],
            [$sintomas, 'Náuseas', 'Tengo náuseas', 'Siento náuseas', '🤢', PictogramSeverity::Warning],
            [$sintomas, 'Fiebre', 'Tengo fiebre', 'Siento que tengo fiebre', '🤒', PictogramSeverity::Warning],
            [$sintomas, 'Falta de aire', 'Me falta el aire', 'Me cuesta respirar', '😮‍💨', PictogramSeverity::Critical],
            [$necesidades, 'Agua', 'Necesito agua', 'Quiero tomar agua', '💧', PictogramSeverity::Neutral],
            [$necesidades, 'Baño', 'Necesito ir al baño', 'Necesito usar el baño', '🚻', PictogramSeverity::Neutral],
            [$respuestas, 'Sí', 'Sí', 'Sí', '✅', PictogramSeverity::Info],
            [$respuestas, 'No', 'No', 'No', '❌', PictogramSeverity::Info],
        ];

        foreach ($pictograms as $i => [$categoria, $title, $phrase, $speech, $emoji, $severity]) {
            Pictogram::create([
                'pictogram_category_id' => $categoria->id,
                'title'       => $title,
                'phrase'      => $phrase,
                'speech_text' => $speech,
                'emoji'       => $emoji,
                'severity'    => $severity->value,
                'sort_order'  => $i + 1,
            ]);
        }
    }
}