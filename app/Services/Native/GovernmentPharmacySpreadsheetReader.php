<?php

namespace App\Services\Native;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class GovernmentPharmacySpreadsheetReader
{
    private const MAX_FILE_BYTES = 5 * 1024 * 1024;

    private const MAX_ROWS = 5000;

    /**
     * @return list<array{cnpj: string, name: string, street: string, district: string}>
     */
    public function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('A planilha de farmácias não foi encontrada.');
        }

        if (filesize($path) > self::MAX_FILE_BYTES) {
            throw new RuntimeException('A planilha de farmácias excede o limite permitido.');
        }

        $archive = new ZipArchive;
        $result = $archive->open($path, ZipArchive::RDONLY);

        if ($result !== true) {
            throw new RuntimeException('Não foi possível abrir a planilha de farmácias.');
        }

        try {
            $sharedStrings = $this->archiveEntry($archive, 'xl/sharedStrings.xml');
            $worksheet = $this->archiveEntry($archive, 'xl/worksheets/sheet1.xml');
        } finally {
            $archive->close();
        }

        $strings = $this->sharedStrings($sharedStrings);
        $rows = $this->worksheetRows($worksheet, $strings);
        $header = array_shift($rows);

        if ($header !== [
            'A' => 'CNPJ',
            'B' => 'Farmácia',
            'C' => 'Endereço',
            'D' => 'Bairro',
        ]) {
            throw new RuntimeException('A planilha não possui o formato oficial esperado.');
        }

        $records = [];
        $seen = [];

        foreach ($rows as $row) {
            $cnpj = preg_replace('/\D/', '', $row['A'] ?? '');
            $name = trim($row['B'] ?? '');
            $street = trim($row['C'] ?? '');
            $district = trim($row['D'] ?? '');

            if ($cnpj === '' && $name === '' && $street === '' && $district === '') {
                continue;
            }

            if (strlen($cnpj) !== 14 || $name === '' || $street === '') {
                throw new RuntimeException('A planilha contém um registro de farmácia inválido.');
            }

            if (isset($seen[$cnpj])) {
                throw new RuntimeException('A planilha contém CNPJ duplicado.');
            }

            $seen[$cnpj] = true;
            $records[] = compact('cnpj', 'name', 'street', 'district');
        }

        if ($records === []) {
            throw new RuntimeException('A planilha não contém farmácias.');
        }

        return $records;
    }

    private function archiveEntry(ZipArchive $archive, string $name): string
    {
        $metadata = $archive->statName($name);

        if ($metadata === false || ($metadata['size'] ?? 0) > self::MAX_FILE_BYTES) {
            throw new RuntimeException('A estrutura interna da planilha é inválida.');
        }

        $contents = $archive->getFromName($name);

        if ($contents === false) {
            throw new RuntimeException('A estrutura interna da planilha é inválida.');
        }

        return $contents;
    }

    /** @return list<string> */
    private function sharedStrings(string $contents): array
    {
        $xml = $this->xml($contents);
        $strings = [];

        foreach ($xml->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $parts = [];

            foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                $parts[] = (string) $text;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<array<string, string>>
     */
    private function worksheetRows(string $contents, array $sharedStrings): array
    {
        $xml = $this->xml($contents);
        $rows = [];

        foreach ($xml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
            if (count($rows) >= self::MAX_ROWS) {
                throw new RuntimeException('A planilha excede o limite de registros permitido.');
            }

            $values = [];

            foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $reference = (string) $cell['r'];

                if (! preg_match('/^[A-Z]+/', $reference, $matches)) {
                    continue;
                }

                $values[$matches[0]] = $this->cellValue($cell, $sharedStrings);
            }

            $rows[] = $values;
        }

        return $rows;
    }

    /** @param list<string> $sharedStrings */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            $parts = array_map(
                static fn (SimpleXMLElement $text): string => (string) $text,
                $cell->xpath('.//*[local-name()="t"]') ?: [],
            );

            return trim(implode('', $parts));
        }

        $nodes = $cell->xpath('./*[local-name()="v"]') ?: [];
        $value = isset($nodes[0]) ? (string) $nodes[0] : '';

        if ($type === 's') {
            return trim($sharedStrings[(int) $value] ?? '');
        }

        return trim($value);
    }

    private function xml(string $contents): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);

            if ($xml === false) {
                throw new RuntimeException('A planilha contém XML inválido.');
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
