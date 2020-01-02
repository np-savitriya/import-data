<?php

namespace Import\ImportData;


use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use App\Models\TimezoneHelper;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


HeadingRowFormatter::default('none');

class DataImport implements OnEachRow, WithHeadingRow, WithMultipleSheets
{
    use Importable;

    public function sheets(): array
    {
        return [
            0 => new DataImport()
        ];
    }
    // public function __construct($type) {
    //     $this->type = $type;
    // }

    public function onRow(Row $row)
    {
        return $row;
        
    }
    
}