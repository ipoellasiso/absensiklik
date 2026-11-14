<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ViewExport implements FromView, ShouldAutoSize, WithTitle
{
    public function __construct(private string $view, private array $data, private string $judul = 'Laporan')
    {
    }

    public function view(): View
    {
        return view($this->view, $this->data);
    }

    public function title(): string
    {
        return substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $this->judul), 0, 31);
    }
}