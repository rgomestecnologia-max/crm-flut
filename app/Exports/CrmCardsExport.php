<?php

namespace App\Exports;

use App\Models\CrmCard;
use App\Models\CrmCustomField;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CrmCardsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $customFields;
    protected ?array $columns;

    // Mapa de colunas disponíveis
    protected array $columnMap = [
        'id'          => 'ID',
        'pipeline'    => 'Pipeline',
        'etapa'       => 'Etapa',
        'titulo'      => 'Título',
        'contato'     => 'Contato',
        'telefone'    => 'Telefone',
        'email'       => 'E-mail',
        'responsavel' => 'Responsável',
        'prioridade'  => 'Prioridade',
        'criado'      => 'Criado em',
    ];

    public function __construct(
        protected ?int $pipelineId = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        ?array $columns = null,
    ) {
        $this->columns = $columns;
        $this->customFields = CrmCustomField::orderBy('sort_order')->get();
    }

    protected function hasColumn(string $key): bool
    {
        return $this->columns === null || in_array($key, $this->columns, true);
    }

    public function collection()
    {
        $query = CrmCard::with(['pipeline', 'stage', 'contact', 'assignedTo', 'fieldValues.field'])
            ->orderBy('pipeline_id')
            ->orderBy('stage_id')
            ->orderBy('sort_order');

        if ($this->pipelineId) {
            $query->where('pipeline_id', $this->pipelineId);
        }
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->get();
    }

    public function headings(): array
    {
        $base = [];
        foreach ($this->columnMap as $key => $label) {
            if ($this->hasColumn($key)) $base[] = $label;
        }

        // Adiciona colunas dos campos personalizados
        if ($this->hasColumn('custom')) {
            foreach ($this->customFields as $field) {
                $base[] = $field->name;
            }
        }

        return $base;
    }

    public function map($card): array
    {
        $allValues = [
            'id'          => $card->id,
            'pipeline'    => $card->pipeline?->name ?? '',
            'etapa'       => $card->stage?->name ?? '',
            'titulo'      => $card->title ?? '',
            'contato'     => $card->contact?->name ?? '',
            'telefone'    => $card->contact?->phone ?? '',
            'email'       => $card->contact?->email ?? '',
            'responsavel' => $card->assignedTo?->name ?? '',
            'prioridade'  => $card->priority_label ?? '',
            'criado'      => $card->created_at?->format('d/m/Y H:i') ?? '',
        ];

        $row = [];
        foreach ($allValues as $key => $value) {
            if ($this->hasColumn($key)) $row[] = $value;
        }

        // Adiciona valores dos campos personalizados
        if ($this->hasColumn('custom')) {
            foreach ($this->customFields as $field) {
                $value = $card->fieldValues
                    ->first(fn($v) => $v->field_id === $field->id)
                    ?->value ?? '';

                if ($field->type === 'currency' && $value !== '') {
                    $value = 'R$ ' . number_format((float) $value, 2, ',', '.');
                }

                $row[] = $value;
            }
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '14B8A6'],
                ],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }
}
