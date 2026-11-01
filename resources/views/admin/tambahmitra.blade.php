<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bakti Mitra - Tambah Akun</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

<body>
    <svg width="0" height="0" style="position:absolute">
        <symbol id="i-home" viewBox="0 0 24 24">
            <path d="M3 11l9-8 9 8M5 10v10h14V10" />
        </symbol>
        <symbol id="i-user" viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c1-4 4-6 8-6s7 2 8 6" />
        </symbol>
        <symbol id="i-plus" viewBox="0 0 24 24">
            <path d="M12 5v14M5 12h14" />
        </symbol>
        <symbol id="i-left" viewBox="0 0 24 24">
            <path d="M15 6l-6 6 6 6" />
        </symbol>
        <symbol id="i-down" viewBox="0 0 24 24">
            <path d="M6 9l6 6 6-6" />
        </symbol>
        <symbol id="i-search" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4-4" />
        </symbol>
        <symbol id="i-bell" viewBox="0 0 24 24">
            <path d="M6 17V11a6 6 0 0112 0v6l2 2H4zM10 21h4" />
        </symbol>
        <symbol id="i-cal" viewBox="0 0 24 24">
            <rect x="3" y="5" width="18" height="16" rx="3" />
            <path d="M3 10h18M8 3v4M16 3v4" />
        </symbol>
        <symbol id="i-chart" viewBox="0 0 24 24">
            <path d="M3 3v18h18M7 15l4-5 3 3 5-6" />
        </symbol>
        <symbol id="i-ring" viewBox="0 0 24 24">
            <path d="M12 3a9 9 0 109 9" />
        </symbol>
        <symbol id="i-list" viewBox="0 0 24 24">
            <path d="M10 6h11M10 12h11M10 18h11M3 6l1.5 1.5L7 5M3 12l1.5 1.5L7 11M3 18l1.5 1.5L7 17" />
        </symbol>
        <symbol id="i-logout" viewBox="0 0 24 24">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
        </symbol>

        <symbol id="i-download" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="M7 10l5 5 5-5" />
            <path d="M12 15V3" />
        </symbol>

        <symbol id="i-filter" viewBox="0 0 24 24">
            <path d="M4 6h16" />
            <path d="M7 12h10" />
            <path d="M10 18h4" />
        </symbol>

    </svg>

    <div class="app" id="app">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo">
                <img class="long" src="{{ asset('images/Logo Bakti Mitra Long.png') }}" alt="Bakti Mitra">
                <img class="short" src="{{ asset('images/Logo Bakti Mitra Short.png') }}" alt="Bakti Mitra">
            </div>
            <nav class="menu">
                <a href="#" title="Keluar"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <svg>
                        <use href="#i-logout" />
                    </svg><span class="label">Keluar</span>
                </a>
                <a href="#" title="Dashboard"><svg>
                        <use href="#i-home" />
                    </svg><span class="label">Dashboard</span></a>
                <a href="{{ route('admin.datamitra') }}" class="active" title="Mitra"><svg>
                        <use href="#i-user" />
                    </svg><span class="label">Mitra</span></a>
                <a href="#" title="Koordinator"><svg>
                        <use href="#i-user" />
                    </svg><span class="label">Koordinator</span></a>
                <div class="has-sub" id="sub">
                    <button type="button" title="Buat Akun"
                        onclick="document.getElementById('sub').classList.toggle('open');document.getElementById('subList').classList.toggle('open')">
                        <svg>
                            <use href="#i-plus" />
                        </svg><span class="label">Buat Akun</span><svg class="chev">
                            <use href="#i-down" />
                        </svg>
                    </button>
                    <div class="submenu" id="subList">
                        <a href="#" title="Buat akun Mitra"><svg>
                                <use href="#i-user" />
                            </svg><span class="label">Mitra</span></a>
                        <a href="#" title="Buat akun Koordinator"><svg>
                                <use href="#i-user" />
                            </svg><span class="label">Koordinator</span></a>
                    </div>
                </div>
            </nav>

            <button type="button" class="toggle" id="toggle" aria-label="Perkecil sidebar" aria-expanded="true"
                title="Perkecil / perbesar sidebar">
                <svg>
                    <use href="#i-left" />
                </svg><span class="label" id="toggleLabel">Perkecil</span>
            </button>
        </aside>

        <main class="create-page">

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
                            <input type="text" id="alamat" name="alamat"
                                placeholder="Masukkan alamat lengkap" required>
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

        </main>

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
            /* Toggle sidebar panjang <-> pendek */
            const app = document.getElementById('app');
            const btn = document.getElementById('toggle');

            function setCollapsed(state) {
                app.classList.toggle('collapsed', state);
                btn.setAttribute('aria-expanded', String(!state));
                btn.setAttribute('aria-label', state ? 'Perbesar sidebar' : 'Perkecil sidebar');
                document.getElementById('toggleLabel').textContent = state ? 'Perbesar' : 'Perkecil';
                try {
                    localStorage.setItem('sidebarCollapsed', state ? '1' : '0');
                } catch (e) {}
            }

            btn.addEventListener('click', () => setCollapsed(!app.classList.contains('collapsed')));

            /* Ingat pilihan terakhir pengguna */
            try {
                if (localStorage.getItem('sidebarCollapsed') === '1') setCollapsed(true);
            } catch (e) {}
        </script>

</body>

</html>
