        <x-admin.main class="create-page">

            <!-- HEADER HALAMAN -->
            <header class="page-header">
                <div class="page-title">
                    <h1>Pembuatan Akun Mitra</h1>
                    <p>Manajemen Data Koordinator Mitra BPS Kota Kendari</p>
                </div>

                <div class="page-actions">
                    <button type="button" class="header-icon">
                        <svg>
                            <use href="#i-cal" />
                        </svg>
                    </button>
                    <button type="button" class="header-icon">
                        <svg>
                            <use href="#i-bell" />
                        </svg>
                    </button>
                    <span class="username">Alyssa</span>
                    <div class="avatar"></div>
                </div>
            </header>

            <!-- FORM MULTI STEP -->
            <form id="mitraForm" action="{{ route('admin.tambahmitra.simpan') }}" method="POST">
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

                <!-- INDIKATOR TAHAP -->
                <div class="step-wrapper">

                    <div class="step-item active" id="stepIndicator1">
                        <div class="step-number">1</div>
                        <span>Data Mitra</span>
                    </div>

                    <div class="step-line" id="stepLine"></div>

                    <div class="step-item" id="stepIndicator2">
                        <div class="step-number">2</div>
                        <span>Akun Mitra</span>
                    </div>

                </div>

                <!-- ======================== -->
                <!-- TAHAP 1: DATA MITRA     -->
                <!-- ======================== -->

                <section class="form-card" id="step1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" value="{{ old('nama') }}"
                                placeholder="Masukkan nama lengkap" required>
                        </div>

                        <div class="form-group">
                            <label for="nik">NIK</label>
                            <input type="text" id="nik" name="nik" value="{{ old('nik') }}"
                                placeholder="16 digit angka" maxlength="16" minlength="16" pattern="[0-9]{16}"
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
                                placeholder="Masukkan alamat lengkap" required>
                        </div>
                    </div>

                    <div class="form-navigation">
                        <button type="button" class="btn-primary" onclick="nextStep()">
                            Selanjutnya
                        </button>
                    </div>
                </section>


                <!-- ======================== -->
                <!-- TAHAP 2: AKUN MITRA     -->
                <!-- ======================== -->

                <section class="form-card hidden" id="step2">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="sobat_id">Sobat ID (digunakan sebagai username)</label>
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
                                placeholder="Ulangi password" minlength="8" required>
                        </div>
                    </div>

                    <div class="form-navigation">
                        <button type="button" class="btn-secondary" onclick="previousStep()">
                            Tahap Sebelumnya
                        </button>
                        <button type="submit" class="btn-primary">
                            Buat Akun
                        </button>
                    </div>
                </section>

            </form>
            @push('scripts')
                <script>
                    const mitraForm = document.getElementById('mitraForm');

                    const step1 = document.getElementById('step1');
                    const step2 = document.getElementById('step2');

                    const indicator1 = document.getElementById('stepIndicator1');
                    const indicator2 = document.getElementById('stepIndicator2');

                    const stepLine = document.getElementById('stepLine');


                    /* PINDAH KE TAHAP 2 */

                    function nextStep() {

                        // Ambil semua input tahap pertama
                        const inputs = step1.querySelectorAll(
                            'input, select'
                        );

                        // Periksa apakah semua input sudah valid
                        for (const input of inputs) {

                            if (!input.checkValidity()) {
                                input.reportValidity();
                                return;
                            }

                        }

                        // Sembunyikan tahap pertama
                        step1.classList.add('hidden');

                        // Tampilkan tahap kedua
                        step2.classList.remove('hidden');

                        // Perbarui indikator
                        indicator1.classList.remove('active');
                        indicator1.classList.add('completed');

                        indicator2.classList.add('active');

                        stepLine.classList.add('completed');
                        simpanDraft();

                    }


                    /* KEMBALI KE TAHAP 1 */

                    function previousStep() {

                        step2.classList.add('hidden');
                        step1.classList.remove('hidden');

                        indicator1.classList.remove('completed');
                        indicator1.classList.add('active');

                        indicator2.classList.remove('active');

                        stepLine.classList.remove('completed');

                        simpanDraft();

                    }


                    /* VALIDASI PASSWORD */

                    mitraForm.addEventListener('submit', function(event) {

                        const password = document.getElementById('password').value;

                        const confirmation = document.getElementById(
                            'password_confirmation'
                        ).value;

                        if (password !== confirmation) {

                            event.preventDefault();

                            alert('Password dan konfirmasi password tidak sama!');

                            document.getElementById(
                                'password_confirmation'
                            ).focus();

                            return;

                        }

                    });

                    /* Jika kesalahan ada di tahap 2, langsung buka tahap 2 */
                    @if ($errors->hasAny(['sobat_id', 'email', 'password']))
                        nextStep();
                    @endif

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
                            // hanya isi kolom yang masih kosong (agar tidak menimpa isian dari server)
                            if (el && !el.value && draft[id]) el.value = draft[id];
                        });

                        if (draft.langkah === 2) nextStep();
                    }

                    mitraForm.addEventListener('input', simpanDraft);
                    mitraForm.addEventListener('change', simpanDraft);
                    pulihkanDraft();
                </script>
            @endpush

        </x-admin.main>
