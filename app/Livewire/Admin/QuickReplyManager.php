<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use App\Models\QuickReply;
use Livewire\Component;

class QuickReplyManager extends Component
{
    public bool   $showForm      = false;
    public ?int   $editingId     = null;
    public string $title         = '';
    public string $content       = '';
    public ?int   $department_id = null;

    public function openCreate(): void
    {
        $this->reset('editingId', 'title', 'content', 'department_id');
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $qr = QuickReply::findOrFail($id);
        $this->editingId     = $id;
        $this->title         = $qr->title;
        $this->content       = $qr->content;
        $this->department_id = $qr->department_id;
        $this->showForm      = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'   => 'required|string|max:100',
            'content' => 'required|string|max:4000',
        ]);

        $data = [
            'title'         => $this->title,
            'content'       => $this->content,
            'department_id' => $this->department_id ?: null,
            'created_by'    => auth()->id(),
        ];

        if ($this->editingId) {
            QuickReply::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Resposta atualizada.');
        } else {
            QuickReply::create($data);
            $this->dispatch('toast', type: 'success', message: 'Resposta rápida criada!');
        }

        $this->showForm = false;
        $this->reset('editingId', 'title', 'content', 'department_id');
    }

    public function delete(int $id): void
    {
        QuickReply::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Resposta removida.');
    }

    public function render()
    {
        $replies     = QuickReply::with('department')->orderBy('title')->get();
        $departments = Department::active()->orderBy('name')->get();
        return view('livewire.admin.quick-reply-manager', compact('replies', 'departments'));
    }
}
