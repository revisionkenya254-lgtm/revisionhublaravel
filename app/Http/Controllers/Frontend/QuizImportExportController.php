<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizImportExportController extends Controller
{
    private const MAX_ROWS = 500;

    private const XSS_PATTERNS = [
        '/<script/i','/javascript:/i','/on\w+=/i','/eval\s*\(/i',
        '/<iframe/i','/<object/i','/data:/i','/vbscript:/i'
    ];

    /* ===================== EXPORT ===================== */

    public function export(): StreamedResponse
    {
        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=quiz_questions_template.csv",
        ];

        $columns = ['question_key','question','grade','option','correct'];

        $rows = [
            [1,'What is Laravel?',10,'PHP Framework',1],
            [1,'What is Laravel?',10,'Programming Language',0],
            [1,'What is Laravel?',10,'Database',0],

            [2,'Which are JS frameworks?',15,'Vue',0],
            [2,'Which are JS frameworks?',15,'React',1],
            [2,'Which are JS frameworks?',15,'Angular',0],
            [2,'Which are JS frameworks?',15,'Svelte',0],
        ];

        return response()->stream(function () use ($columns, $rows) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, $columns);

            foreach ($rows as $row) {
                fputcsv($f, $row);
            }

            fclose($f);
        }, 200, $headers);
    }
    public function importModal($quiz_id) {
        return view('frontend.instructor-dashboard.course.partials.quiz-question-import-modal', ['quizId' => $quiz_id])->render();
    }

    /* ===================== IMPORT ===================== */

    public function import(Request $request, Quiz $quiz)
    {
        $request->validate([
            'quiz_csv' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($quiz->user_id && auth()->id() !== $quiz->user_id) {
            return back()->with('alert-type','error')
                ->with('messege',__('unauthorized access'));
        }

        $file = fopen($request->file('quiz_csv')->getRealPath(), 'r');
        if (!$file) {
            return back()->with('alert-type','error')
                ->with('messege',__('Unable to read file.'));
        }

        $header = fgetcsv($file);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $required = ['question_key','question','grade','option','correct'];
        if ($missing = array_diff($required, $header)) {
            fclose($file);
            return back()->with('alert-type','error')
                ->with('messege',__('Missing columns: :cols',[
                    'cols' => implode(', ', $missing)
                ]));
        }

        $rows = [];
        while (($data = fgetcsv($file)) && count($rows) < self::MAX_ROWS) {
            if (count($data) !== count($header)) {
                fclose($file);
                return back()->with('alert-type','error')
                    ->with('messege',__('CSV column mismatch.'));
            }

            $row = array_combine($header, array_map('trim', $data));
            $this->sanitizeRow($row);
            $rows[] = $row;
        }

        fclose($file);

        DB::beginTransaction();
        try {
            $grouped = collect($rows)->groupBy('question_key');

            foreach ($grouped as $key => $group) {
                if ($group->pluck('question')->unique()->count() !== 1) {
                    throw new \Exception(
                        "Question key {$key} has inconsistent question text."
                    );
                }
                if ($group->pluck('grade')->unique()->count() !== 1) {
                    throw new \Exception(
                        "Question key {$key} has inconsistent grade."
                    );
                }
                if ($group->count() < 2) {
                    throw new \Exception("Question key {$key} must have at least 2 options.");
                }

                if ($group->where('correct', 1)->count() !== 1) {
                    throw new \Exception("Question key {$key} must have exactly one correct option.");
                }

                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'title'   => $group->first()['question'],
                    'grade'   => $group->first()['grade'],
                ]);

                foreach ($group as $row) {
                    $question->answers()->create([
                        'title'   => $row['option'],
                        'correct' => (int) $row['correct'],
                    ]);
                }
            }

            DB::commit();

            return back()->with('alert-type','success')
                ->with('messege',__('Quiz imported successfully.'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('alert-type','error')
                ->with('messege',$e->getMessage());
        }
    }

    /* ===================== SECURITY ===================== */

    private function sanitizeRow(array &$row): void
    {
        foreach ($row as $key => &$value) {
            foreach (self::XSS_PATTERNS as $pattern) {
                if (preg_match($pattern, $value)) {
                    Log::warning('XSS detected in quiz import', [
                        'field' => $key,
                        'value' => substr($value,0,200),
                        'user_id' => auth()->id(),
                    ]);
                }
            }
            $value = strip_tags($value);
        }
    }
}
