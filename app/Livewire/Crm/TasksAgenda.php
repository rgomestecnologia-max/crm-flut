<?php

namespace App\Livewire\Crm;

use App\Models\CrmCardTask;
use Livewire\Component;

class TasksAgenda extends Component
{
    public string $selectedDate = '';

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function toggleTask(int $taskId): void
    {
        $task = CrmCardTask::find($taskId);
        if (!$task) return;
        $task->update([
            'is_completed' => !$task->is_completed,
            'completed_at' => !$task->is_completed ? now() : null,
        ]);
    }

    public function render()
    {
        $tasks = CrmCardTask::with(['card.contact', 'card.pipeline', 'card.stage', 'user'])
            ->dueOn($this->selectedDate)
            ->orderBy('is_completed')
            ->orderBy('due_time')
            ->get();

        $pending   = $tasks->where('is_completed', false)->count();
        $completed = $tasks->where('is_completed', true)->count();

        return view('livewire.crm.tasks-agenda', compact('tasks', 'pending', 'completed'));
    }
}
