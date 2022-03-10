<?php

namespace App\Console\Commands;

use App\Components\Imports\Customers\Import;
use App\Exceptions\ReadFileException;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

class ImportCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт клиентов';

    /**
     * Execute the console command.
     *
     * @param  Import  $customers_import
     * @return void
     * @throws ReadFileException|Exception
     */
    public function handle(Import $customers_import): void
    {
        $customers_import->import($this->output);
    }
}
