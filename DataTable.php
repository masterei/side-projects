<?php

/**
 * @author          : Rei Junior
 * @dateCreated     : November  23, 2023
 * @github          : https://github.com/masterei
 * @description     : A simple datatable data builder.
 *
 * @dependency      : https://www.datatables.net
 *
 * This source file is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.
 */

class DataTable
{
    protected array $source = [];

    protected array $editColumns = [];

    protected array $addColumns = [];

    protected ?int $totalRecords = null;

    public function __construct(array $source)
    {
        $this->source = $source;
    }

    public static function source(array $data): self
    {
        return new self($data);
    }

    public function totalRecords(int $count): self
    {
        $this->totalRecords = $count;
        return $this;
    }

    public function editColumn(string $column, callable $fn): self
    {
        $this->editColumns[$column] = $fn;
        return $this;
    }

    public function addColumn(string $column, callable $fn): self
    {
        $this->addColumns[] = [
            'column' => $column,
            'callable' => $fn
        ];
        return $this;
    }

    private function create(): array
    {
        $data = [];

        // mapping source data
        foreach ($this->source as $source) {
            // converting to object type
            $source = json_decode(json_encode($source));

            // adding column
            foreach ($this->addColumns as $addColumn) {
                $source->{$addColumn['column']} = $addColumn['callable']($source);
            }

            // editing column
            foreach ($source as $key => $value) {
                if (array_key_exists($key, $this->editColumns)) {
                    $source->{$key} = $this->editColumns[$key]($source);
                }
            }

            $data[] = $source;
        }

        return [
            'data' => $data,
            'draw' => $_GET['draw'] ?? 0,
            'recordsFiltered' => $this->totalRecords,
            'recordsTotal' => $this->totalRecords
        ];
    }

    public function make(): array
    {
        return $this->create();
    }

    public function makeJson(): string
    {
        return json_encode($this->create());
    }

    public function makeResponseJson(): string
    {
        header('Content-Type: application/json; charset=utf-8');
        return $this->makeJson();
    }
}
