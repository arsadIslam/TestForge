<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\User;
use App\Services\QuestionService;
use GdImage;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;

/**
 * Demo questions with pictures — drawn here with GD, so no binary files live in git:
 *   1. "Which shape has exactly three sides?" — the four OPTIONS are images (image-only options);
 *   2. a bar chart as the QUESTION image, with text options.
 * Saved through QuestionService, exactly like the teacher form (random file names, derived type).
 */
class DiagramQuestionSeeder extends Seeder
{
    /** @var list<string> */
    private array $tempFiles = [];

    public function run(QuestionService $questions): void
    {
        $teacher = User::where('email', 'teacher2@testforge.test')->firstOrFail();
        $geometry = Topic::where('slug', 'geometry')->firstOrFail();
        $probability = Topic::where('slug', 'probability')->firstOrFail();

        try {
            $questions->create($teacher, [
                'topic_id' => $geometry->id,
                'body' => 'Which shape has exactly three sides?',
                'explanation' => 'A triangle has three sides; a square has four, a pentagon five and a hexagon six.',
                'difficulty' => 'easy', 'marks' => 1, 'negative_marks' => 0, 'is_active' => true,
                'tag_names' => ['Diagram Based'],
                'options' => [
                    ['text' => '', 'is_correct' => false, 'image' => $this->polygon(4)],
                    ['text' => '', 'is_correct' => true, 'image' => $this->polygon(3)],
                    ['text' => '', 'is_correct' => false, 'image' => $this->polygon(6)],
                    ['text' => '', 'is_correct' => false, 'image' => $this->polygon(5)],
                ],
            ]);

            $questions->create($teacher, [
                'topic_id' => $probability->id,
                'body' => 'The chart shows a shop\'s sales for four months. In which month were sales highest?',
                'explanation' => 'March has the tallest bar (45 units).',
                'difficulty' => 'easy', 'marks' => 1, 'negative_marks' => 0.25, 'is_active' => true,
                'tag_names' => ['Diagram Based'],
                'image' => $this->barChart(['Jan' => 20, 'Feb' => 35, 'Mar' => 45, 'Apr' => 30]),
                'options' => [
                    ['text' => 'January', 'is_correct' => false],
                    ['text' => 'February', 'is_correct' => false],
                    ['text' => 'March', 'is_correct' => true],
                    ['text' => 'April', 'is_correct' => false],
                ],
            ]);
        } finally {
            array_map('unlink', array_filter($this->tempFiles, 'is_file'));
        }
    }

    /**
     * A regular polygon with $sides sides, as a PNG.
     */
    private function polygon(int $sides): File
    {
        $image = imagecreatetruecolor(240, 240);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

        $points = [];
        for ($i = 0; $i < $sides; $i++) {
            $angle = -M_PI / 2 + 2 * M_PI * $i / $sides;
            $points[] = (int) round(120 + 90 * cos($angle));
            $points[] = (int) round(128 + 90 * sin($angle));
        }

        imagefilledpolygon($image, $points, imagecolorallocate($image, 199, 210, 254));
        imagesetthickness($image, 4);
        imagepolygon($image, $points, imagecolorallocate($image, 67, 56, 202));

        return $this->png($image);
    }

    /**
     * @param  array<string, int>  $values  label => value (0–50)
     */
    private function barChart(array $values): File
    {
        $image = imagecreatetruecolor(480, 300);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $axis = imagecolorallocate($image, 100, 116, 139);
        $bar = imagecolorallocate($image, 79, 70, 229);

        imageline($image, 50, 250, 460, 250, $axis);
        imageline($image, 50, 30, 50, 250, $axis);

        foreach ([0, 10, 20, 30, 40, 50] as $tick) {
            $y = 250 - $tick * 4;
            imagestring($image, 3, 18, $y - 7, (string) $tick, $axis);
            imageline($image, 46, $y, 50, $y, $axis);
        }

        $x = 80;
        foreach ($values as $label => $value) {
            imagefilledrectangle($image, $x, 250 - $value * 4, $x + 60, 249, $bar);
            imagestring($image, 4, $x + 18, 258, $label, $axis);
            $x += 95;
        }

        return $this->png($image);
    }

    private function png(GdImage $image): File
    {
        $path = tempnam(sys_get_temp_dir(), 'tf-seed-').'.png';
        imagepng($image, $path);
        $this->tempFiles[] = $path;

        return new File($path);
    }
}
