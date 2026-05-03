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

    public function __construct(
        protected ?int $pipelineId = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
    ) {
        $this->customFields = CrmCustomField::orderBy('sort_order')->get();
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
        $base = [
            'ID',
            'Pipeline',
            'Etapa',
            'Título',
            'Contato',
            'Telefone',
            'E-mail',
            'Responsável',
            'Prioridade',
            'Criado em',
        ];

        // Adiciona colunas dos campos personalizados
        foreach ($this->customFields as $field) {
            $base[] = $field->name;
        }

        return $base;
    }

    public function map($card): array
    {
        $row = [
            $card->id,
            $card->pipeline?->name ?? '',
            $card->stage?->name ?? '',
            $card->title ?? '',
            $card->contact?->name ?? '',
            $card->contact?->phone ?? '',
            $card->contact?->email ?? '',
            $card->assignedTo?->name ?? '',
            $card->priority_label ?? '',
            $card->created_at?->format('d/m/Y H:i') ?? '',
        ];

        // Adiciona valores dos campos personalizados
        foreach ($this->customFields as $field) {
            $value = $card->fieldValues
                ->first(fn($v) => $v->field_id === $field->id)
                ?->value ?? '';

            // Formata valores de moeda
            if ($field->type === 'currency' && $value !== '') {
                $value = 'R$ ' . number_format((float) $value, 2, ',', '.');
            }

            $row[] = $value;
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
