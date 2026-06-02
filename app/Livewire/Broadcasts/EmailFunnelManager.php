<?php

namespace App\Livewire\Broadcasts;

use App\Models\EmailFunnel;
use App\Models\EmailFunnelStep;
use App\Models\EmailFunnelSubscriber;
use App\Models\BroadcastContact;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmailFunnelManager extends Component
{
    public string $tab = 'list'; // list, editor, subscribers

    // CRUD
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $triggerType = 'manual';
    public string $triggerValue = '';

    // Editor
    public ?int $editingFunnelId = null;
    public string $addStepType = 'email';

    // Subscribers
    public string $contactSearch = '';
    public array $selectedContactIds = [];

    public function saveFunnel(): void
    {
        $this->validate(['name' => 'required|string|max:200']);

        $data = [
            'name'          => $this->name,
            'trigger_type'  => $this->triggerType,
            'trigger_value' => $this->triggerValue ?: null,
        ];

        if ($this->editingId) {
            EmailFunnel::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Funil atualizado.');
        } else {
            $data['created_by'] = Auth::id();
            $funnel = EmailFunnel::create($data);
            // Steps iniciais
            EmailFunnelStep::create([
                'funnel_id'  => $funnel->id,
                'type'       => 'email',
                'sort_order' => 1,
                'config'     => ['subject' => 'Bem-vindo!', 'html_content' => '<h2>Olá {nome}!</h2><p>Obrigado por se cadastrar.</p>', 'from_name' => ''],
            ]);
            EmailFunnelStep::create([
                'funnel_id'  => $funnel->id,
                'type'       => 'delay',
                'sort_order' => 2,
                'config'     => ['seconds' => 172800, 'label' => '2 dias'],
            ]);
            EmailFunnelStep::create([
                'funnel_id'  => $funnel->id,
                'type'       => 'email',
                'sort_order' => 3,
                'config'     => ['subject' => 'Como podemos ajudar?', 'html_content' => '<h2>Olá {nome}!</h2><p>Gostaríamos de saber como podemos ajudar.</p>', 'from_name' => ''],
            ]);
            $this->dispatch('toast', type: 'success', message: 'Funil criado com steps iniciais.');
        }
        $this->resetForm();
    }

    public function editFunnel(int $id): void
    {
        $f = EmailFunnel::findOrFail($id);
        $this->editingId    = $f->id;
        $this->name         = $f->name;
        $this->triggerType  = $f->trigger_type;
        $this->triggerValue = $f->trigger_value ?? '';
        $this->showForm     = true;
    }

    public function deleteFunnel(int $id): void
    {
        EmailFunnel::findOrFail($id)->delete();
        if ($this->editingFunnelId === $id) $this->editingFunnelId = null;
        $this->dispatch('toast', type: 'success', message: 'Funil excluído.');
    }

    public function toggleFunnelStatus(int $id): void
    {
        $f = EmailFunnel::findOrFail($id);
        $newStatus = match($f->status) {
            'draft'  => 'active',
            'active' => 'paused',
            'paused' => 'active',
            default  => 'active',
        };
        $f->update(['status' => $newStatus]);
        $this->dispatch('toast', type: 'success', message: 'Status: ' . $newStatus);
    }

    // Editor de steps
    public function openEditor(int $funnelId): void
    {
        $this->editingFunnelId = $funnelId;
        $this->tab = 'editor';
    }

    public function addStep(): void
    {
        if (!$this->editingFunnelId) return;
        $maxOrder = EmailFunnelStep::where('funnel_id', $this->editingFunnelId)->max('sort_order') ?? 0;

        $config = match($this->addStepType) {
            'email'     => ['subject' => 'Novo email', 'html_content' => '<h2>Olá {nome}!</h2><p>Seu conteúdo aqui.</p>', 'from_name' => ''],
            'delay'     => ['seconds' => 86400, 'label' => '1 dia'],
            'condition' => ['field' => 'opened', 'step_id_ref' => null, 'true_next' => null, 'false_next' => null],
            default     => [],
        };

        EmailFunnelStep::create([
            'funnel_id'  => $this->editingFunnelId,
            'type'       => $this->addStepType,
            'sort_order' => $maxOrder + 1,
            'config'     => $config,
        ]);
        $this->dispatch('toast', type: 'success', message: 'Step adicionado.');
    }

    public function updateStepConfig(int $stepId, string $key, $value): void
    {
        $step = EmailFunnelStep::find($stepId);
        if (!$step) return;
        $config = $step->config ?? [];
        $config[$key] = $value;
        $step->update(['config' => $config]);
    }

    public function moveStepUp(int $id): void
    {
        $step = EmailFunnelStep::findOrFail($id);
        $prev = EmailFunnelStep::where('funnel_id', $step->funnel_id)
            ->where('sort_order', '<', $step->sort_order)->orderByDesc('sort_order')->first();
        if ($prev) {
            $tmp = $step->sort_order;
            $step->update(['sort_order' => $prev->sort_order]);
            $prev->update(['sort_order' => $tmp]);
        }
    }

    public function moveStepDown(int $id): void
    {
        $step = EmailFunnelStep::findOrFail($id);
        $next = EmailFunnelStep::where('funnel_id', $step->funnel_id)
            ->where('sort_order', '>', $step->sort_order)->orderBy('sort_order')->first();
        if ($next) {
            $tmp = $step->sort_order;
            $step->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tmp]);
        }
    }

    public function deleteStep(int $id): void
    {
        EmailFunnelStep::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Step removido.');
    }

    // Subscribers
    public function openSubscribers(int $funnelId): void
    {
        $this->editingFunnelId = $funnelId;
        $this->tab = 'subscribers';
    }

    public function addSubscribers(): void
    {
        if (!$this->editingFunnelId || empty($this->selectedContactIds)) return;
        $funnel = EmailFunnel::find($this->editingFunnelId);
        if (!$funnel) return;

        $firstStep = $funnel->steps()->orderBy('sort_order')->first();
        $count = 0;

        foreach ($this->selectedContactIds as $contactId) {
            $exists = EmailFunnelSubscriber::where('funnel_id', $funnel->id)->where('contact_id', $contactId)->exists();
            if ($exists) continue;

            EmailFunnelSubscriber::create([
                'funnel_id'       => $funnel->id,
                'contact_id'      => $contactId,
                'current_step_id' => $firstStep?->id,
                'status'          => 'active',
                'step_entered_at' => now(),
                'entered_at'      => now(),
            ]);
            $count++;
        }

        $this->selectedContactIds = [];
        $this->contactSearch = '';
        $this->dispatch('toast', type: 'success', message: "{$count} contato(s) adicionado(s) ao funil.");
    }

    public function removeSubscriber(int $id): void
    {
        EmailFunnelSubscriber::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Contato removido do funil.');
    }

    public function toggleContactSelect(int $id): void
    {
        if (in_array($id, $this->selectedContactIds)) {
            $this->selectedContactIds = array_values(array_diff($this->selectedContactIds, [$id]));
        } else {
            $this->selectedContactIds[] = $id;
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->triggerType = 'manual';
        $this->triggerValue = '';
        $this->showForm = false;
    }

    public function render()
    {
        $funnels = EmailFunnel::withCount('subscribers')->orderByDesc('updated_at')->get();

        $steps = collect();
        if ($this->editingFunnelId && $this->tab === 'editor') {
            $steps = EmailFunnelStep::where('funnel_id', $this->editingFunnelId)->orderBy('sort_order')->get();
        }

        $subscribers = collect();
        $contacts = collect();
        if ($this->editingFunnelId && $this->tab === 'subscribers') {
            $subscribers = EmailFunnelSubscriber::where('funnel_id', $this->editingFunnelId)
                ->with('contact', 'currentStep')->latest()->get();
            $contacts = BroadcastContact::where('is_active', true)
                ->when($this->contactSearch, fn($q) =>
                    $q->where('name', 'like', "%{$this->contactSearch}%")
                      ->orWhere('email', 'like', "%{$this->contactSearch}%")
                      ->orWhere('phone', 'like', "%{$this->contactSearch}%")
                )->orderBy('name')->take(30)->get();
        }

        return view('livewire.broadcasts.email-funnel-manager', compact('funnels', 'steps', 'subscribers', 'contacts'));
    }
}
