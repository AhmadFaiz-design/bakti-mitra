<x-admin.main>
    <x-slot:titlex>Buat Akun Koordinator</x-slot:titlex>

    <!-- HEADER HALAMAN -->
    <header class="page-header">
        <div class="page-title">
            <h1>Pembuatan Akun Koordinator</h1>
            <p>Manajemen Data Koordinator BPS Kota Kendari</p>
        </div>

        <div class="page-actions">
            <button type="button" class="header-icon" aria-label="Kalender" title="Kalender">
                <svg><use href="#i-cal" /></svg>
            </button>
            <button type="button" class="header-icon" aria-label="Notifikasi" title="Notifikasi">
                <svg><use href="#i-bell" /></svg>
            </button>
            <span class="username">{{ auth()->user()->name }}</span>
            <div class="avatar" style="display:flex;align-items:center;justify-content:center">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- FORM SATU TAHAP -->
    <form id="koorForm" action="{{ route('admin.tambahkoor.simpan') }}" method="POST">
        @csrf

        @if ($errors->any())
            <div role="alert"
                style="background:#fdecea;border:1px solid #b3261e;color:#b3261e;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px">
                <b>Data belum bisa disimpan:</b>
                <ul style="margin:6px 0 0 18px;padding:0">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="form-card">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}"
                        placeholder="Masukkan nama lengkap" required>
                </div>

                <div class="form-group">
                    <label for="nip">NIP (digunakan sebagai username)</label>
                    <input type="text" id="nip" name="nip" value="{{ old('nip') }}"
                        placeholder="18 digit angka" maxlength="18" minlength="18" pattern="[0-9]{18}"
                        title="NIP harus 18 digit angka" inputmode="numeric" required>
                </div>

                <div class="form-group full-width">
                    <label for="email">Email (opsional)</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        placeholder="Masukkan alamat email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 8 karakter"
                        minlength="8" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        placeholder="Ulangi password" minlength="8" required>
                </div>
            </div>

            <div class="form-navigation">
                <button type="submit" class="btn-primary">Buat Akun</button>
            </div>
        </section>
    </form>

    @push('scripts')
        <script>
            document.getElementById('koorForm').addEventListener('submit', function(event) {
                const password = document.getElementById('password').value;
                const konfirmasi = document.getElementById('password_confirmation').value;

                if (password !== konfirmasi) {
                    event.preventDefault();

                    const info = {
                        jenis: 'gagal',
                        judul: 'Password tidak sama',
                        pesan: 'Password dan konfirmasi password harus sama.'
                    };
                    if (typeof popup === 'function') popup(info);
                    else alert(info.pesan);

                    document.getElementById('password_confirmation').focus();
                }
            });
        </script>
    @endpush
</x-admin.main>
