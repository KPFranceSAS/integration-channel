<?php

namespace App\Helper\Utils;



class CsvExtracter
{

    public function extractDatasFromCsv(string $pathFile, ?string $separator = ';'): array
    {
        $contentFile = fopen($pathFile, "r");
        $datas = [];
        while (($values = fgetcsv($contentFile, null, $separator)) !== false) {
            $datas[] = $values;
        }
        return $datas;
    }


    public function extractAssociativeDatasFromCsv(string $pathFile, ?string $separator = ';'): array
    {
        $contentFile = fopen($pathFile, "r");
        $datas = [];
        $header = fgetcsv($contentFile, null, $separator);
        while (($values = fgetcsv($contentFile, null, $separator)) !== false) {
            if (count($values) == count($header)) {
                $dataLines = array_combine($header, $values);
                $datas[] = $dataLines;
            }
        }
        return $datas;
    }
}
