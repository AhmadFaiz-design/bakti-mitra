<x-admin.main>
    {{-- HEADER HALAMAN --}}
    <header class="page-heading">
        <div>
            <h1>Penugasan Mitra</h1>
            <p>Pekerjaan yang diberikan kepada mitra BPS Kota Kendari</p>
        </div>

        <div class="header-actions">
            <div class="header-user">
                <span>{{ auth()->user()->name }}</span>
                <div class="avatar" style="width:48px;height:48px">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </div>
    </header>

    {{-- PENCARIAN --}}
    <section class="mitra-toolbar">
        <form method="GET" action="{{ route('admin.penugasan') }}" style="display: contents">
            <label class="mitra-search">
                <svg>
                    <use href="#i-search" />
                </svg>
                <input type="search" name="q" value="{{ $q }}"
                    placeholder="Cari nama mitra atau uraian tugas..." autocomplete="off">
            </label>
        </form>
    </section>

    {{-- TABEL PENUGASAN --}}
    <section class="table-card">
        <div class="table-heading">
            <h2>
                <svg>
                    <use href="#i-list" />
                </svg>
                Daftar Penugasan
            </h2>

            <a href="{{ route('admin.penugasan.buat') }}" class="primary-btn" style="text-decoration:none">
                <svg>
                    <use href="#i-plus" />
                </svg>
                Beri Tugas
            </a>
        </div>

        <div class="table-scroll">
            <table class="mitra-table">
                <thead>
                    <tr>
                        <th>Mitra</th>
                        <th>Tugas</th>
                        <th>Koordinator</th>
                        <th>Periode</th>
                        <th>Volume</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($daftar as $p)
                        @php($mitra = $p->kontrak?->mitra)
                        <tr>
                            <td>
                                <span class="profile-name">{{ $mitra?->user?->name ?? '-' }}</span><br>
                                <small class="id-cell">{{ $mitra?->user?->username }}</small>
                            </td>
                            <td>{{ $p->uraian_pekerjaan }}</td>
                            <td>{{ $p->koordinator?->name ?? '-' }}</td>
                            <td>
                                {{ $p->tanggal_mulai?->locale('id')->translatedFormat('j M Y') }}
                                –
                                {{ $p->tanggal_selesai?->locale('id')->translatedFormat('j M Y') }}
                            </td>
                            <td>
                                {{ rtrim(rtrim(number_format($p->volume, 2, ',', '.'), '0'), ',') }}
                                {{ $p->satuan }}
                            </td>
                            <td class="honor-cell">Rp {{ number_format($p->harga_satuan ?? 0, 0, ',', '.') }}</td>
                            <td class="honor-cell">Rp {{ number_format($p->jumlah ?? 0, 0, ',', '.') }}</td>
                            <td>
                                <button type="button" class="outline-btn btn-hapus-tugas"
                                    style="color:#b3261e"
                                    data-url="{{ route('admin.penugasan.hapus', $p->id) }}"
                                    data-uraian="{{ $p->uraian_pekerjaan }}"
                                    data-mitra="{{ $mitra?->user?->name }}">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:24px">
                                @if ($q !== '')
                                    Tidak ada tugas yang cocok dengan "{{ $q }}".
                                @else
                                    Belum ada tugas yang diberikan.
                                    <a href="{{ route('admin.penugasan.buat') }}">Beri tugas sekarang</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span>
                @if ($daftar->total() > 0)
                    Menampilkan {{ $daftar->firstItem() }}–{{ $daftar->lastItem() }}
                    dari {{ $daftar->total() }} tugas
                @else
                    Tidak ada data
                @endif
            </span>

            @if ($daftar->hasPages())
                <span style="display:flex;gap:8px;align-items:center">
                    @unless ($daftar->onFirstPage())
                        <a class="outline-btn" style="text-decoration:none" href="{{ $daftar->url(1) }}"
                            aria-label="Halaman pertama" title="Halaman pertama">&lt;&lt;</a>
                        <a class="outline-btn" style="text-decoration:none" href="{{ $daftar->previousPageUrl() }}"
                            aria-label="Halaman sebelumnya" title="Halaman sebelumnya">&lt;</a>
                    @endunless

                    <span>Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }}</span>

                    @if ($daftar->hasMorePages())
                        <a class="outline-btn" style="text-decoration:none" href="{{ $daftar->nextPageUrl() }}"
                            aria-label="Halaman berikutnya" title="Halaman berikutnya">&gt;</a>
                        <a class="outline-btn" style="text-decoration:none"
                            href="{{ $daftar->url($daftar->lastPage()) }}" aria-label="Halaman terakhir"
                            title="Halaman terakhir">&gt;&gt;</a>
                    @endif
                </span>
            @endif
        </div>
    </section>

    <form id="hapusPenugasanForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    @push('scripts')
        <script>
            (function() {
                const form = document.getElementById('hapusPenugasanForm');

                document.querySelectorAll('.btn-hapus-tugas').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const lanjut = await popup({
                            jenis: 'hapus',
                            judul: 'Hapus tugas?',
                            pesan: 'Tugas "' + btn.dataset.uraian + '" untuk ' + btn.dataset.mitra +
                                ' akan dihapus dan tidak bisa dikembalikan.',
                            tombolOk: 'Ya, hapus',
                            tombolBatal: 'Batal'
                        });

                        if (lanjut) {
                            form.action = btn.dataset.url;
                            form.submit();
                        }
                    });
                });
            })();
        </script>
    @endpush
</x-admin.main>
