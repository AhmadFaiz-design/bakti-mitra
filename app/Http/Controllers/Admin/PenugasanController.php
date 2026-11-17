<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Shared\BasePenugasanController;

class PenugasanController extends BasePenugasanController
{
    protected string $role = 'admin';
    protected string $layout = 'admin.main';
    protected string $indexRoute = 'admin.penugasan';
    protected string $routePrefix = 'admin';
    protected string $exportView = 'admin.export-penugasan';
    protected bool $hanyaTugasSendiri = false;
    protected bool $bisaPilihKoordinator = true;
    protected bool $bisaKelolaBayar = true;
}
