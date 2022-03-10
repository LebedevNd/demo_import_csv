<?php

namespace App\Components\ExcelCreator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelCreator
{
    protected const START_LINE = 1;
    protected const START_COLUMN = 'A';

    /**
     * @var string[]
     */
    private $title;

    /**
     * @var string[][]
     */
    private $body;

    /**
     * @var string
     */
    private $file_path;

    /**
     * @var Worksheet
     */
    protected $sheet;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var int
     */
    protected $active_line;

    public function __construct(array $title, array $body, string $file_path)
    {
        $this->title = $title;
        $this->body = $body;
        $this->file_path = $file_path;
        $this->active_line = self::START_LINE;

        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    /**
     * @throws Exception
     */
    public function export()
    {
        $this->formSheet();

        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->file_path);
    }

    private function formSheet()
    {
        $this->fillTableLine($this->title);
        $this->setTableBody();
    }

    function getNextColumnIndex(string $column_index): string
    {
        return (string) ++$column_index;
    }

    private function setTableBody()
    {
        foreach ($this->body as $line) {
            $this->fillTableLine($line);
        }
    }

    /**
     * @param  string[]  $line
     */
    private function fillTableLine(array $line)
    {
        $column = self::START_COLUMN;

        foreach ($line as $item) {
            $this->sheet->setCellValue($column.$this->active_line, $item);
            $column = $this->getNextColumnIndex($column);
        }
        ++$this->active_line;
    }
}
