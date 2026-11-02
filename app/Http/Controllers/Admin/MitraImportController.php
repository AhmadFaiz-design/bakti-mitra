<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\MitraImport;
use Illuminate\Http\Request;

class MitraImportController extends Controller
{
    public function form()
    {
        return view('admin.mitra-import');
    }

    public function proses(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes'    => 'File harus berformat .xlsx atau .xls.',
            'file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $file = $request->file('file');

        if (!$file->isValid()) {
            return back()->withErrors(['file' => 'Upload gagal: ' . $file->getErrorMessage()]);
        }

        $import = new MitraImport;

        try {
            $import->jalankan($file->getPathname(), $file->getClientOriginalExtension());
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['file' => 'File tidak bisa dibaca: ' . $e->getMessage()]);
        }

        return back()->with('hasil', [
            'berhasil' => $import->berhasil,
            'gagal'    => $import->gagal,
        ]);
    }
}
