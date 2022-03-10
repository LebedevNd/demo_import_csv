<?php

namespace App\Components\Imports\Customers;

use App\Components\ExcelCreator\ExcelCreator;
use App\Components\Imports\Customers\Dto\Customer;
use App\Exceptions\ReadFileException;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class Import
{
    private const DEFAULT_FILE_PATH = 'imports/customers/file.csv';
    private const DEFAULT_ERRORS_FILE_PATH = 'imports/customers/errors/';

    private const NAME_COLUMN_NUM = 0;
    private const SURNAME_COLUMN_NUM = 1;
    private const EMAIL_COLUMN_NUM = 2;
    private const AGE_COLUMN_NUM = 3;
    private const LOCATION_COLUMN_NUM = 4;
    private const COUNTRY_CODE_COLUMN_NUM = 5;

    private const UNKNOWN_LOCATION = 'Unknown';

    /**
     * @var CustomerService
     */
    private $service;

    /**
     * @var string
     */
    private $file_path;

    /**
     * @var Customer[]
     */
    private $invalid_customers = [];

    /**
     * @var int
     */
    private $customers_created = 0;

    /**
     * @var int
     */
    private $customers_updated = 0;

    /**
     * @var int
     */
    private $customers_with_errors = 0;

    /**
     * @var string
     */
    private string $errors_file_path;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
        $this->file_path = storage_path(self::DEFAULT_FILE_PATH);
        $this->errors_file_path = storage_path(self::DEFAULT_ERRORS_FILE_PATH);
    }

    /**
     * @throws ReadFileException
     * @throws Exception
     */
    public function import(OutputInterface $output): void
    {
        $csv_lines = $this->extractArrayFromCsv();
        $this->processCsvLines($csv_lines);
        $this->logCustomers($output);

        if (!empty($this->invalid_customers)) {
            $this->exportErrorsExcel();
        }
    }

    /**
     * @return string[][]
     * @throws ReadFileException
     */
    private function extractArrayFromCsv(): array
    {
        /** @var string[]|false $file_array */
        $file_array = file($this->file_path);
        if ($file_array === false) {
            throw new ReadFileException('Не удалось открыть файл');
        }

        /** @var string[][] $data_lines */
        $data_lines = array_map('str_getcsv', $file_array);
        array_shift($data_lines);
        return $data_lines;
    }

    /**
     * @param  string[][]  $csv_lines
     */
    private function processCsvLines(array $csv_lines): void
    {
        foreach ($csv_lines as $csv_line) {
            $this->processCsvLine($csv_line);
        }
    }

    /**
     * @param  string[]  $csv_line
     */
    private function processCsvLine(array $csv_line): void
    {
        $customer_dto = $this->formCustomerDto($csv_line);
        if ($customer_dto === null) {
            ++$this->customers_with_errors;
            return;
        }

        $customer = $this->service->storeCustomer($customer_dto);
        $this->updateCustomersCounter($customer->wasRecentlyCreated);
    }

    /**
     * @param  string[]  $csv_line
     * @return Customer|null
     */
    private function formCustomerDto(array $csv_line): ?Customer
    {
        $customer_dto = new Customer(
            $csv_line[self::NAME_COLUMN_NUM],
            $csv_line[self::SURNAME_COLUMN_NUM],
            $csv_line[self::EMAIL_COLUMN_NUM],
            $csv_line[self::AGE_COLUMN_NUM],
            $csv_line[self::LOCATION_COLUMN_NUM],
            $csv_line[self::COUNTRY_CODE_COLUMN_NUM],
        );

        $error_column = $this->validateCustomer($customer_dto);

        if ($error_column) {
            $customer_dto->error_column = $error_column;
            $this->invalid_customers[] = $customer_dto;

            return null;
        }

        if (!$this->validateLocation($customer_dto->location)) {
            $customer_dto->location = self::UNKNOWN_LOCATION;
        }

        return $customer_dto;
    }

    private function validateCustomer(Customer $customer_dto): ?string
    {
        $validator = Validator::make((array) $customer_dto, [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|string|max:255|email:rfc,dns',
            'age' => 'required|integer|min:18|max:99',
            'country_code' => 'required|string|max:3',
        ]);

        if ($validator->fails()) {
            return implode(', ', $validator->errors()->keys());
        }

        return null;
    }

    private function validateLocation(string $location): bool
    {
        return !(strlen($location) > 255);
    }

    private function updateCustomersCounter(bool $wasRecentlyCreated): void
    {
        if ($wasRecentlyCreated) {
            ++$this->customers_created;
        } else {
            ++$this->customers_updated;
        }
    }

    private function logCustomers(OutputInterface $output): void
    {
        if ($this->customers_created > 0) {
            $output->writeln('Создано клиентов: '.$this->customers_created);
        }

        if ($this->customers_updated > 0) {
            $output->writeln('Обновлено клиентов: '.$this->customers_updated);
        }

        if ($this->customers_with_errors > 0) {
            $output->writeln('Количество клиентов с ошибками валидации: '.$this->customers_with_errors);
        }
    }

    /**
     * @throws Exception
     */
    private function exportErrorsExcel(): void
    {
        $title = $this->formErrorsTitle();
        $body = $this->formErrorsBody();

        $excel_creator = new ExcelCreator($title, $body, $this->errors_file_path.'errors.xls');
        $excel_creator->export();
    }

    /**
     * @return string[]
     */
    private function formErrorsTitle(): array
    {
        return [
            'Имя',
            'Фамилия',
            'Email',
            'Возраст',
            'Адрес',
            'Код страны',
            'Ошибки'
        ];
    }

    /**
     * @return string[][]
     */
    private function formErrorsBody(): array
    {
        $body = [];
        foreach ($this->invalid_customers as $invalid_customer) {
            $line = [
                $invalid_customer->name,
                $invalid_customer->surname,
                $invalid_customer->email,
                $invalid_customer->age,
                $invalid_customer->location,
                $invalid_customer->country_code,
                $invalid_customer->error_column
            ];
            $body[] = $line;
        }

        return $body;
    }
}
