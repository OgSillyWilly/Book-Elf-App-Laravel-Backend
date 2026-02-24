<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\BooksImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BookImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // Max 10MB
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
                        $fail('Het bestand moet een Excel (.xlsx, .xls) of CSV (.csv) bestand zijn.');
                    }
                },
            ],
        ]);

        try {
            Excel::import(new BooksImport, $request->file('file'));

            // Clear cache after import
            cache()->flush();

            return response()->json([
                'message' => 'Boeken succesvol geïmporteerd!',
            ], 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            
            foreach ($failures as $failure) {
                $errors[] = "Regel {$failure->row()}: " . implode(', ', $failure->errors());
            }
            
            return response()->json([
                'message' => 'Validatie fouten in het Excel bestand',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fout bij importeren: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function template()
    {
        $headers = [
            'titel',
            'auteur',
            'isbn',
            'type',
            'uitgever',
            'publicatiedatum',
            'kast',
            'plank',
            'positie',
            'gelezen',
            'slipcase',
            'dustjacket',
            'startdatum',
            'einddatum',
        ];

        $csv = implode(',', $headers) . "\n";
        $csv .= '"Voorbeeld Boek","Voorbeeld Auteur","9781234567890","Roman","Uitgeverij","2024-01-15","1","2","1","1","0","1","2024-01-01","2024-01-30"';

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="boeken_template.csv"');
    }
}
