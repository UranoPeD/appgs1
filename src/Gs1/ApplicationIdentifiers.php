<?php

declare(strict_types=1);

namespace App\Gs1;

final class ApplicationIdentifiers
{
    /** @var array<string, array{label: string, type: string}> */
    private const MAP = [
        '00' => ['label' => 'SSCC', 'type' => 'text'],
        '01' => ['label' => 'GTIN', 'type' => 'gtin'],
        '02' => ['label' => 'GTIN do conteúdo', 'type' => 'gtin'],
        '10' => ['label' => 'Lote', 'type' => 'text'],
        '11' => ['label' => 'Data de produção', 'type' => 'date'],
        '13' => ['label' => 'Data de embalagem', 'type' => 'date'],
        '15' => ['label' => 'Consumir até', 'type' => 'date'],
        '17' => ['label' => 'Data de validade', 'type' => 'date'],
        '21' => ['label' => 'Número de série', 'type' => 'text'],
        '22' => ['label' => 'Dados secundários', 'type' => 'text'],
        '30' => ['label' => 'Quantidade', 'type' => 'text'],
        '37' => ['label' => 'Contagem', 'type' => 'text'],
        '240' => ['label' => 'Identificação adicional', 'type' => 'text'],
        '241' => ['label' => 'Número de peça do cliente', 'type' => 'text'],
        '243' => ['label' => 'Componente da embalagem', 'type' => 'text'],
        '250' => ['label' => 'Número de série secundário', 'type' => 'text'],
        '251' => ['label' => 'Referência da origem', 'type' => 'text'],
        '400' => ['label' => 'Pedido do cliente', 'type' => 'text'],
        '412' => ['label' => 'GLN do comprador', 'type' => 'text'],
        '414' => ['label' => 'GLN', 'type' => 'text'],
        '415' => ['label' => 'GLN de faturamento', 'type' => 'text'],
        '422' => ['label' => 'País de origem', 'type' => 'text'],
        '423' => ['label' => 'País de processamento', 'type' => 'text'],
        '424' => ['label' => 'País de montagem', 'type' => 'text'],
        '425' => ['label' => 'País da cadeia de processo', 'type' => 'text'],
        '426' => ['label' => 'País de cobertura total', 'type' => 'text'],
        '8008' => ['label' => 'Data e hora de produção', 'type' => 'text'],
        '90' => ['label' => 'Informação interna', 'type' => 'text'],
    ];

    /**
     * @return array{label: string, type: string}
     */
    public static function meta(string $ai): array
    {
        if (isset(self::MAP[$ai])) {
            return self::MAP[$ai];
        }

        if (preg_match('/^310(\d)$/', $ai, $m)) {
            return ['label' => 'Peso líquido (kg)', 'type' => 'decimal:' . $m[1] . ':kg'];
        }

        if (preg_match('/^320(\d)$/', $ai, $m)) {
            return ['label' => 'Peso líquido (lb)', 'type' => 'decimal:' . $m[1] . ':lb'];
        }

        if (preg_match('/^315(\d)$/', $ai, $m)) {
            return ['label' => 'Volume líquido (l)', 'type' => 'decimal:' . $m[1] . ':l'];
        }

        if (preg_match('/^392(\d)$/', $ai)) {
            return ['label' => 'Valor a pagar', 'type' => 'amount:' . substr($ai, -1)];
        }

        if (preg_match('/^393(\d)$/', $ai)) {
            return ['label' => 'Valor a pagar (com moeda)', 'type' => 'amount_iso:' . substr($ai, -1)];
        }

        return ['label' => 'AI ' . $ai, 'type' => 'text'];
    }
}
