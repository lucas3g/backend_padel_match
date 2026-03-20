<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GerarMunicipiosIbge extends Command
{
    protected $signature = 'ibge:gerar-municipios';

    protected $description = 'Busca os municípios do IBGE e salva em resources/data/municipios_ibge.json';

    public function handle(): int
    {
        $this->info('Buscando municípios da API do IBGE...');

        $response = file_get_contents('https://servicodados.ibge.gov.br/api/v1/localidades/municipios');

        if ($response === false) {
            $this->error('Falha ao conectar na API do IBGE.');
            return 1;
        }

        $dados = json_decode($response, true);

        if (!$dados) {
            $this->error('Resposta inválida da API do IBGE.');
            return 1;
        }

        $municipios = array_map(fn ($m) => [
            'id' => (string) $m['id'],
            'uf' => $m['microrregiao']['mesorregiao']['UF']['sigla']
                ?? $m['regiao-imediata']['regiao-intermediaria']['UF']['sigla']
                ?? null,
        ], $dados);

        $dir = resource_path('data');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $dir . '/municipios_ibge.json',
            json_encode($municipios, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        $this->info(count($municipios) . ' municípios salvos em resources/data/municipios_ibge.json');

        return 0;
    }
}
