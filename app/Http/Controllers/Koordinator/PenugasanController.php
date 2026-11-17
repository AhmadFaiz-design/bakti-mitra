<?php

namespace App\Http\Controllers\Koordinator;

use App\Http\Controllers\Shared\BasePenugasanController;

class PenugasanController extends BasePenugasanController
{
    protected string $role = 'koordinator';
    protected string $layout = 'koordinator.main';
    protected string $indexRoute = 'koordinator.datamitra';
    protected string $routePrefix = 'koordinator';
    protected string $exportView = 'admin.export-penugasan'; // ganti ke 'koordinator.export-penugasan' jika ada view export khusus koordinator
    protected bool $hanyaTugasSendiri = true;
    protected bool $bisaPilihKoordinator = false;
    protected bool $bisaKelolaBayar = false; // ubah ke true jika Koordinator juga boleh menandai pembayaran
}
