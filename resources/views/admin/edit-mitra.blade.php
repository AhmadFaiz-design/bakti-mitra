@php
    $wilayahList = ['Kendari', 'Mandonga', 'Baruga', 'Poasia', 'Kadia', 'Abeli'];
    // Wilayah hasil import (misalnya Konawe) tetap tampil sebagai pilihan
    if ($mitra->wilayah && !in_array($mitra->wilayah, $wilayahList)) {
        $wilayahList[] = $mitra->wilayah;
    }
    $jk = old('jenis_kelamin', $mitra->jenis_kelamin);
    $status = (string) old('is_active', $mitra->user->is_active ? '1' : '0');
@endphp

<x-admin.main class="create-page">

    {{-- HEADER HALAMAN --}}
    <header class="page-header">
        <div class="page-title">
            <h1>Edit Akun Mitra</h1>
            <p>Ubah data dan akun mitra BPS Kota Kendari</p>
        </div>

        <div class="page-actions">
            <button type="button" class="header-icon" aria-label="Kalender" title="Kalender">
                <svg>
                    <use href="#i-cal" />
                </svg>
            </button>
            <button type="button" class="header-icon" aria-label="Notifikasi" title="Notifikasi">
                <svg>
                    <use href="#i-bell" />
                </svg>
            </button>
            <span class="username">{{ auth()->user()->name }}</span>
            <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    <form id="editMitraForm" action="{{ route('admin.mitra.update', $mitra) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- DAFTAR KESALAHAN --}}
        @if ($errors->any())
            <div role="alert"
                style="background:#fdecea;border:1px solid #b3261e;color:#b3261e;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px">
                <b>Perubahan belum bisa disimpan:</b>
                <ul style="margin:6px 0 0 18px;padding:0">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ======================== --}}
        {{-- DATA MITRA               --}}
        {{-- ======================== --}}
        <section class="form-card">
            <h3 style="margin:0 0 16px;font-size:16px">Data Mitra</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama', $mitra->user->name) }}"
                        placeholder="Masukkan nama lengkap" required>
                </div>

                <div class="form-group">
                    <label for="nik">NIK</label>
                    <input type="text" id="nik" name="nik" value="{{ old('nik', $mitra->nik) }}"
                        placeholder="16 digit angka" maxlength="16" minlength="16" pattern="[0-9]{16}"
                        title="NIK harus 16 digit angka" inputmode="numeric" required>
                </div>

                <div class="form-group">
                    <label for="pekerjaan">Pekerjaan</label>
                    <input type="text" id="pekerjaan" name="pekerjaan"
                        value="{{ old('pekerjaan', $mitra->pekerjaan) }}" placeholder="Masukkan pekerjaan">
                </div>

                <div class="form-group">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin">
                        <option value="" @selected(!$jk)>Belum diisi</option>
                        <option value="Lk" @selected($jk === 'Lk')>Laki-laki</option>
                        <option value="Pr" @selected($jk === 'Pr')>Perempuan</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="wilayah">Wilayah</label>
                    <select id="wilayah" name="wilayah">
                        <option value="">Belum diisi</option>
                        @foreach ($wilayahList as $w)
                            <option value="{{ $w }}" @selected(old('wilayah', $mitra->wilayah) === $w)>{{ $w }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="alamat" value="{{ old('alamat', $mitra->alamat) }}"
                        placeholder="Masukkan alamat lengkap">
                </div>

            </div>
        </section>

        {{-- ======================== --}}
        {{-- AKUN MITRA               --}}
        {{-- ======================== --}}
        <section class="form-card" style="margin-top:16px">
            <h3 style="margin:0 0 16px;font-size:16px">Akun Mitra</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label for="sobat_id">Sobat ID (username login)</label>
                    <input type="text" id="sobat_id" name="sobat_id"
                        value="{{ old('sobat_id', $mitra->user->username) }}" placeholder="Masukkan Sobat ID"
                        required>
                </div>

                <div class="form-group">
                    <label for="email">Email (opsional)</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $mitra->user->email) }}"
                        placeholder="Masukkan alamat email">
                </div>

                <div class="form-group full-width">
                    <label for="is_active">Status Akun</label>
                    <select id="is_active" name="is_active" required>
                        <option value="1" @selected($status === '1')>Aktif</option>
                        <option value="0" @selected($status === '0')>Tidak Aktif (tidak bisa login)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password Baru</label>
                    <input type="password" id="password" name="password" minlength="8"
                        placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
                        placeholder="Ulangi password baru" autocomplete="new-password">
                </div>

            </div>

            {{-- TOMBOL --}}
            <div class="form-navigation">
                <a href="{{ route('admin.datamitra') }}" class="btn-secondary"
                    style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center">
                    Batal
                </a>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </section>
    </form>

    @push('scripts')
        <script>
            /* Pastikan password baru dan konfirmasinya sama (jika diisi) */
            document.getElementById('editMitraForm').addEventListener('submit', function(event) {
                const password = document.getElementById('password').value;
                const konfirmasi = document.getElementById('password_confirmation').value;

                if (password !== '' && password !== konfirmasi) {
                    event.preventDefault();
                    alert('Password baru dan konfirmasi password tidak sama!');
                    document.getElementById('password_confirmation').focus();
                }
            });
        </script>
    @endpush

</x-admin.main>
