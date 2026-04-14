<?php

namespace App\Livewire\Admin;

use App\Models\CrmCustomField;
use Livewire\Component;

class CrmCustomFieldManager extends Component
{
    public bool   $showForm   = false;
    public ?int   $editingId  = null;
    public string $field_name = '';
    public string $field_type = 'text';
    public bool   $field_required = false;

    public array $types = [
        'text'     => 'Texto',
        'textarea' => 'Texto longo',
        'number'   => 'Número',
        'currency' => 'Valor (R$)',
        'date'     => 'Data',
        'time'     => 'Horário',
        'datetime' => 'Data + Horário',
        'email'    => 'E-mail',
        'phone'    => 'Telefone',
        'url'      => 'Link (URL)',
    ];

    public function openCreate(): void
    {
        $this->reset(['editingId', 'field_name', 'field_required']);
        $this->field_type = 'text';
        $this->showForm   = true;
    }

    public function openEdit(int $id): void
    {
        $f = CrmCustomField::findOrFail($id);
        $this->editingId      = $id;
        $this->field_name     = $f->name;
        $this->field_type     = $f->type;
        $this->field_required = $f->is_required;
        $this->showForm       = true;
    }

    public function save(): void
    {
        $this->validate([
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|in:' . implode(',', array_keys($this->types)),
        ]);

        if ($this->editingId) {
            $field = CrmCustomField::findOrFail($this->editingId);
            $field->update([
                'name'        => $this->field_name,
                'type'        => $this->field_type,
                'is_required' => $this->field_required,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Campo atualizado.');
        } else {
            $key = CrmCustomField::generateKey($this->field_name);
            // Garante key única
            $base = $key;
            $n    = 2;
            while (CrmCustomField::where('key', $key)->exists()) {
                $key = $base . '_' . $n++;
            }

            CrmCustomField::create([
                'name'        => $this->field_name,
                'key'         => $key,
                'type'        => $this->field_type,
                'is_required' => $this->field_required,
                'sort_order'  => CrmCustomField::max('sort_order') + 1,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Campo criado.');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'field_name', 'field_required']);
        $this->field_type = 'text';
    }

    public function delete(int $id): void
    {
        CrmCustomField::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Campo removido.');
    }

    public function moveUp(int $id): void   { $this->reorder($id, 'up'); }
    public function moveDown(int $id): void { $this->reorder($id, 'down'); }

    private function reorder(int $id, string $dir): void
    {
        $list  = CrmCustomField::orderBy('sort_order')->get();
        $index = $list->search(fn($f) => $f->id === $id);
        if ($index === false) return;
        $swap = $dir === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= $list->count()) return;
        [$list[$index]->sort_order, $list[$swap]->sort_order] =
            [$list[$swap]->sort_order, $list[$index]->sort_order];
        $list[$index]->save();
        $list[$swap]->save();
    }

    public function render()
    {
        return view('livewire.admin.crm-custom-field-manager', [
            'fields' => CrmCustomField::orderBy('sort_order')->get(),
        ]);
    }
}
