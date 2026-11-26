<x-admin.main>
    <x-slot:titlex>Pembuatan Akun Mitra</x-slot:titlex>

    <div class="create-page">
        {{-- HEADER HALAMAN (tanpa subjudul) --}}
        <x-header judul="Pembuatan Akun Mitra" />

        {{-- FORM MULTI STEP --}}
        <form id="mitraForm" action="{{ route('admin.tambahmitra.simpan') }}" method="POST">
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

            {{-- INDIKATOR TAHAP (PILL ANIMASI) --}}
            <div class="step-wrapper">
                <div class="step-pill" id="stepPill" data-step="1" aria-label="Tahap pembuatan akun">
                    <div class="step-pill-track">
                        <span class="step-pill-fill" aria-hidden="true"></span>
                        <span class="step-num step-num-1">1</span>
                        <span class="step-num step-num-2">2</span>
                    </div>
                </div>
            </div>

            {{-- ======================== --}}
            {{-- TAHAP 1: DATA MITRA      --}}
            {{-- ======================== --}}
            <section class="form-card" id="step1">
                <h2 class="form-card-heading">
                    <svg viewBox="0 0 24 24">
                        <rect x="4" y="3" width="16" height="18" rx="2.5" />
                        <circle cx="12" cy="10" r="2.5" />
                        <path d="M8 17c.8-2 2.2-3 4-3s3.2 1 4 3" />
                    </svg>
                    Data Mitra
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" value="{{ old('nama') }}"
                            placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" id="nik" name="nik" value="{{ old('nik') }}"
                            placeholder="16 Digit Angka" maxlength="16" minlength="16" pattern="[0-9]{16}"
                            title="NIK harus 16 digit angka" inputmode="numeric" required>
                    </div>

                    <div class="form-group">
                        <label for="pekerjaan">Pekerjaan</label>
                        <input type="text" id="pekerjaan" name="pekerjaan" value="{{ old('pekerjaan') }}"
                            placeholder="Masukkan pekerjaan" required>
                    </div>

                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="" selected disabled>Pilih Jenis Kelamin</option>
                            <option value="Lk" @selected(old('jenis_kelamin') === 'Lk')>Laki-laki</option>
                            <option value="Pr" @selected(old('jenis_kelamin') === 'Pr')>Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="wilayah">Wilayah</label>
                        <select id="wilayah" name="wilayah" required>
                            <option value="" selected disabled>Pilih Wilayah</option>
                            @foreach (['Kota Kendari', 'Kab. Konawe Kepulauan'] as $w)
                                <option value="{{ $w }}" @selected(old('wilayah') === $w)>{{ $w }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" value="{{ old('alamat') }}"
                            placeholder="Masukkan Alamat Lengkap" required>
                    </div>
                </div>

                <div class="form-navigation">
                    <button type="button" class="dark-pill-btn" onclick="nextStep()">
                        <span>Selanjutnya</span>
                        <svg viewBox="0 0 24 24">
                            <path d="M9 6l6 6-6 6" />
                        </svg>
                    </button>
                </div>
            </section>

            {{-- ======================== --}}
            {{-- TAHAP 2: AKUN MITRA      --}}
            {{-- ======================== --}}
            <section class="form-card hidden" id="step2">
                <h2 class="form-card-heading">
                    <svg viewBox="0 0 24 24">
                        <rect x="4" y="3" width="16" height="18" rx="2.5" />
                        <circle cx="12" cy="10" r="2.5" />
                        <path d="M8 17c.8-2 2.2-3 4-3s3.2 1 4 3" />
                    </svg>
                    Akun Mitra
                </h2>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="sobat_id">Sobat ID (sebagai username)</label>
                        <input type="text" id="sobat_id" name="sobat_id" value="{{ old('sobat_id') }}"
                            placeholder="Masukkan Sobat ID" required>
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
                    <button type="button" class="outline-btn" onclick="previousStep()">
                        <svg viewBox="0 0 24 24">
                            <path d="M15 6l-6 6 6 6" />
                        </svg>
                        <span>Sebelumnya</span>
                    </button>

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
            const mitraForm = document.getElementById('mitraForm');
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const stepPill = document.getElementById('stepPill');

            /* PINDAH KE TAHAP 2 */
            function nextStep() {
                const inputs = step1.querySelectorAll('input, select');
                for (const input of inputs) {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        return;
                    }
                }

                step1.classList.add('hidden');
                step2.classList.remove('hidden');
                stepPill.dataset.step = '2';

                simpanDraft();
            }

            /* KEMBALI KE TAHAP 1 */
            function previousStep() {
                step2.classList.add('hidden');
                step1.classList.remove('hidden');
                stepPill.dataset.step = '1';

                simpanDraft();
            }

            /* VALIDASI PASSWORD */
            mitraForm.addEventListener('submit', function(event) {
                const password = document.getElementById('password').value;
                const confirmation = document.getElementById('password_confirmation').value;

                if (password !== confirmation) {
                    event.preventDefault();

                    popup({
                        jenis: 'gagal',
                        judul: 'Password tidak sama',
                        pesan: 'Password dan konfirmasi password harus sama.'
                    });

                    document.getElementById('password_confirmation').focus();
                }
            });

            /* DRAF FORM: bertahan saat halaman di-refresh */
            const KUNCI_DRAFT = 'draftTambahMitra';
            const kolomDraft = ['nama', 'nik', 'pekerjaan', 'jenis_kelamin', 'wilayah', 'alamat', 'sobat_id', 'email'];

            function simpanDraft() {
                const draft = {
                    langkah: step2.classList.contains('hidden') ? 1 : 2
                };
                kolomDraft.forEach(id => {
                    draft[id] = document.getElementById(id).value;
                });
                try {
                    sessionStorage.setItem(KUNCI_DRAFT, JSON.stringify(draft));
                } catch (e) {}
            }

            function pulihkanDraft() {
                let draft = null;
                try {
                    draft = JSON.parse(sessionStorage.getItem(KUNCI_DRAFT));
                } catch (e) {}
                if (!draft) return;

                kolomDraft.forEach(id => {
                    const el = document.getElementById(id);
                    if (el && !el.value && draft[id]) el.value = draft[id];
                });

                if (draft.langkah === 2) nextStep();
            }

            mitraForm.addEventListener('input', simpanDraft);
            mitraForm.addEventListener('change', simpanDraft);
            pulihkanDraft();

            /* Jika kesalahan ada di tahap 2, langsung buka tahap 2 */
            @if ($errors->hasAny(['sobat_id', 'email', 'password']))
                nextStep();
            @endif
        </script>
    @endpush
</x-admin.main>
