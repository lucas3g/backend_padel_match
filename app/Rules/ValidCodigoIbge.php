<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCodigoIbge implements ValidationRule
{
    public function __construct(private readonly ?string $uf = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $codigo = str_pad((string) $value, 7, '0', STR_PAD_LEFT);

        if (!preg_match('/^\d{7}$/', $codigo)) {
            $fail('O código IBGE do município deve conter exatamente 7 dígitos numéricos.');
            return;
        }

        $municipios = $this->carregarMunicipios();

        if (!isset($municipios[$codigo])) {
            $fail('O código IBGE informado não corresponde a nenhum município brasileiro.');
            return;
        }

        if ($this->uf !== null) {
            $ufMunicipio = $municipios[$codigo];
            if (strtoupper($this->uf) !== $ufMunicipio) {
                $fail("O código IBGE informado pertence ao estado {$ufMunicipio}, não à UF informada.");
            }
        }
    }

    private function carregarMunicipios(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $path = resource_path('data/municipios_ibge.json');
        $raw = json_decode(file_get_contents($path), true);
        $cache = array_column($raw, 'uf', 'id');

        return $cache;
    }
}
