<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/cidades_brasil.csv');
        if (!file_exists($path)) {
            $this->command->error("CSV não encontrado em: {$path}");
            return;
        }

        if (($h = fopen($path, 'r')) === false) {
            $this->command->error("Não foi possível abrir o CSV.");
            return;
        }

        // cabeçalho: name,state,ibge_code (UTF-8, separado por vírgula)
        $header = fgetcsv($h, 0, ',');
        $header = array_map(fn($v) => trim(mb_convert_encoding($v, 'UTF-8', 'UTF-8')), $header);

        $map = array_flip($header); // ['name'=>0,'state'=>1,'ibge_code'=>2]

        $batch = [];
        $now = now();
        $total = 0;

        while (($row = fgetcsv($h, 0, ',')) !== false) {
            $name = trim($row[$map['name']] ?? '');
            $uf   = strtoupper(trim($row[$map['state']] ?? ''));
            $ibge = trim((string)($row[$map['ibge_code']] ?? ''));

            if ($name === '' || $uf === '') continue;

            $batch[] = [
                'name'       => $name,
                'state'      => $uf,
                'ibge_code'  => $ibge !== '' ? $ibge : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 1000) {
                City::upsert($batch, ['name','state'], ['ibge_code','updated_at']);
                $total += count($batch);
                $batch = [];
            }
        }

        fclose($h);

        if (!empty($batch)) {
            City::upsert($batch, ['name','state'], ['ibge_code','updated_at']);
            $total += count($batch);
        }

        $this->command->info("Cidades importadas/atualizadas: {$total}");
    }
}
