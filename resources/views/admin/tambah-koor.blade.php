<x-admin.main>
    <x-slot:titlex>Buat Akun Koordinator</x-slot:titlex>

    <div class="create-page">
        {{-- HEADER HALAMAN (tanpa subjudul) --}}
        <x-header judul="Pembuatan Akun Koordinator" />

        {{-- FORM SATU TAHAP --}}
        <form id="koorForm" action="{{ route('admin.tambahkoor.simpan') }}" method="POST">
            @csrf

            @if ($errors->any())
                <div class="form-alert-error" role="alert">
                    <b>Data belum bisa disimpan:</b>
                    <ul>
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="form-card">
                <h2 class="form-card-heading">
                    <svg viewBox="0 0 24 24">
                        <rect x="4" y="3" width="16" height="18" rx="2.5" />
                        <circle cx="12" cy="10" r="2.5" />
                        <path d="M8 17c.8-2 2.2-3 4-3s3.2 1 4 3" />
                    </svg>
                    Akun Koordinator
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" value="{{ old('nama') }}"
                            placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="form-group">
                        <label for="nip">NIP (sebagai username)</label>
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
                            placeholder="Ulangi Password" minlength="8" required>
                    </div>
                </div>

                <div class="form-navigation">
                    <button type="submit" class="dark-pill-btn">
                        <span>Buat Akun</span>
                        <svg>
                            <use href="#i-plus-circle" />
                        </svg>
                    </button>
                </div>
            </section>
        </form>
    </div>

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
