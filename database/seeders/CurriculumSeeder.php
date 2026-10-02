<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

/**
 * A starter list of courses, levels and skills. Edit it to match the
 * programme actually taught; the app reads whatever is in the database.
 */
class CurriculumSeeder extends Seeder
{
    private const CURRICULUM = [
        'Handwriting (Print)' => [
            'Pre-writing' => ['Pencil grip', 'Posture and paper position', 'Straight lines', 'Curves and circles'],
            'Letter formation' => ['Lowercase letters', 'Capital letters', 'Numbers 0 to 9'],
            'Words and sentences' => ['Letter sizing', 'Spacing between words', 'Sitting on the line', 'Writing speed'],
        ],
        'Handwriting (Cursive)' => [
            'Cursive letters' => ['Lowercase cursive', 'Capital cursive'],
            'Joining' => ['Bottom joins', 'Top joins', 'Joining capitals'],
            'Fluency' => ['Consistent slant', 'Sentence writing', 'Writing speed'],
        ],
        'Phonics' => [
            'Letter sounds' => [
                's a t i p n', 'c k e h r m d', 'g o u l f b', 'ai j oa ie ee or',
                'z w ng v oo', 'y x ch sh th', 'qu ou oi ue er ar',
            ],
            'Blending and segmenting' => ['Blending CVC words', 'Segmenting CVC words', 'Consonant blends', 'Tricky words set 1'],
            'Digraphs and alternatives' => ['Alternative spellings', 'Split digraphs', 'Tricky words set 2', 'Reading short sentences'],
        ],
    ];

    public function run(): void
    {
        foreach (self::CURRICULUM as $courseName => $levels) {
            $course = Course::firstOrCreate(['name' => $courseName]);

            foreach (array_keys($levels) as $i => $levelName) {
                $level = $course->levels()->firstOrCreate(['name' => $levelName], ['sort_order' => $i + 1]);

                foreach ($levels[$levelName] as $j => $skillName) {
                    $level->skills()->firstOrCreate(['name' => $skillName], ['sort_order' => $j + 1]);
                }
            }
        }
    }
}
