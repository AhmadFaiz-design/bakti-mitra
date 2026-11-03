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
            <form id="mitraForm" action="#" method="POST">
                @csrf

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

                        <!-- NAMA LENGKAP -->
                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama"
                                placeholder="Contoh: Prabowo (Londo Ireng)" required>
                        </div>

                        <!-- NIK -->
                        <div class="form-group">
                            <label for="nik">NIK</label>
                            <input type="text" id="nik" name="nik" placeholder="Masukkan NIK"
                                maxlength="16" inputmode="numeric" required>
                        </div>

                        <!-- PEKERJAAN -->
                        <div class="form-group">
                            <label for="pekerjaan">Pekerjaan</label>
                            <input type="text" id="pekerjaan" name="pekerjaan" placeholder="Contoh: Presiden"
                                required>
                        </div>

                        <!-- JENIS KELAMIN -->
                        <div class="form-group">
                            <label for="jenis_kelamin">Jenis Kelamin</label>
                            <select id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="" selected disabled>
                                    Pilih Jenis Kelamin
                                </option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>

                        <!-- WILAYAH -->
                        <div class="form-group full-width">
                            <label for="wilayah">Wilayah</label>
                            <select id="wilayah" name="wilayah" required>
                                <option value="" selected disabled>
                                    Pilih Wilayah
                                </option>
                                <option value="Kendari">Kendari</option>
                                <option value="Mandonga">Mandonga</option>
                                <option value="Baruga">Baruga</option>
                                <option value="Poasia">Poasia</option>
                                <option value="Kadia">Kadia</option>
                                <option value="Abeli">Abeli</option>
                            </select>
                        </div>

                        <!-- ALAMAT -->
                        <div class="form-group full-width">
                            <label for="alamat">Alamat</label>
                            <input type="text" id="alamat" name="alamat" placeholder="Masukkan alamat lengkap"
                                required>
                        </div>

                    </div>

                    <!-- TOMBOL LANJUT -->
                    <div class="form-navigation">
                        <button type="button" class="btn-primary" onclick="nextStep()">
                            Lanjut Tahap Selanjutnya
                        </button>
                    </div>

                </section>


                <!-- ======================== -->
                <!-- TAHAP 2: AKUN MITRA     -->
                <!-- ======================== -->

                <section class="form-card hidden" id="step2">

                    <div class="form-grid">

                        <!-- SOBAT ID -->
                        <div class="form-group full-width">
                            <label for="sobat_id">Sobat ID</label>
                            <input type="text" id="sobat_id" name="sobat_id" placeholder="Masukkan Sobat ID"
                                required>
                        </div>

                        <!-- EMAIL -->
                        <div class="form-group full-width">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Masukkan alamat email"
                                required>
                        </div>

                        <!-- PASSWORD -->
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Masukkan password"
                                minlength="8" required>
                        </div>

                        <!-- KONFIRMASI PASSWORD -->
                        <div class="form-group">
                            <label for="password_confirmation">
                                Konfirmasi Password
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                placeholder="Ulangi password" minlength="8" required>
                        </div>

                    </div>

                    <!-- TOMBOL NAVIGASI -->
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

                }


                /* KEMBALI KE TAHAP 1 */

                function previousStep() {

                    step2.classList.add('hidden');
                    step1.classList.remove('hidden');

                    indicator1.classList.remove('completed');
                    indicator1.classList.add('active');

                    indicator2.classList.remove('active');

                    stepLine.classList.remove('completed');

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
            </script>
        @endpush

        </x-admin.main>
