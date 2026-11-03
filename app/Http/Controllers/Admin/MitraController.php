<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use Illuminate\Http\Request;

class MitraController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status') === 'inactive' ? 'inactive' : 'active';
        $sort   = in_array($request->query('sort'), ['honor-desc', 'honor-asc'])
            ? $request->query('sort')
            : 'name';
        $q = trim((string) $request->query('q', ''));

        $query = Mitra::query()
            ->join('users', 'users.id', '=', 'mitra.user_id')
            ->select('mitra.*')
            ->with('user')
            ->withSum('pekerjaan', 'jumlah')
            ->where('users.is_active', $status === 'active');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                  ->orWhere('users.username', 'like', "%{$q}%")
                  ->orWhere('mitra.nik', 'like', "%{$q}%");
            });
        }

        if ($sort === 'honor-desc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) DESC')->orderBy('users.name');
        } elseif ($sort === 'honor-asc') {
            $query->orderByRaw('COALESCE(pekerjaan_sum_jumlah, 0) ASC')->orderBy('users.name');
        } else {
            $query->orderBy('users.name');
        }

        $daftarMitra = $query->paginate(10)->withQueryString();

        // Statistik dihitung dari seluruh data, bukan hanya halaman ini
        $total    = Mitra::count();
        $aktif    = Mitra::whereHas('user', fn ($u) => $u->where('is_active', true))->count();
        $nonaktif = $total - $aktif;

        return view('admin.manajemen-mitra', compact(
            'daftarMitra', 'total', 'aktif', 'nonaktif', 'status', 'sort', 'q'
        ));
    }
}
