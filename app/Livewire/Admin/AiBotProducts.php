<?php

namespace App\Livewire\Admin;

use App\Models\AiBotProduct;
use App\Services\MediaStorage;
use Livewire\Component;
use Livewire\WithFileUploads;

class AiBotProducts extends Component
{
    use WithFileUploads;

    // Form state
    public bool   $showForm   = false;
    public ?int   $editingId  = null;

    // Fields
    public string  $type        = 'produto';
    public string  $name        = '';
    public string  $description = '';
    public bool    $show_price  = false;
    public string  $price       = '';
    public bool    $is_active   = true;
    public         $photo       = null;
    public ?string $existingPhoto = null;
    public string  $documentText = '';
    public ?string $existingDocument = null;

    public function openCreate(): void
    {
        $this->reset(['editingId','name','description','show_price','price','photo','existingPhoto','documentText','existingDocument']);
        $this->type      = 'produto';
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $item = AiBotProduct::findOrFail($id);
        $this->editingId        = $id;
        $this->type             = $item->type;
        $this->name             = $item->name;
        $this->description      = $item->description ?? '';
        $this->show_price       = $item->show_price;
        $this->price            = $item->price ? (string) $item->price : '';
        $this->is_active        = $item->is_active;
        $this->photo            = null;
        $this->existingPhoto    = $item->photo_path;
        $this->documentText     = $item->document_content ?? '';
        $this->existingDocument = $item->document_path;
        $this->showForm         = true;
    }

    public function save(): void
    {
        $this->validate([
            'type'        => 'required|in:produto,servico',
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'show_price'  => 'boolean',
            'price'       => 'nullable|numeric|min:0',
            'photo'       => 'nullable|image|max:4096',
            'documentText' => 'nullable|string|max:50000',
            'is_active'   => 'boolean',
        ]);

        $data = [
            'type'        => $this->type,
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'show_price'  => $this->show_price,
            'price'       => $this->show_price && $this->price ? $this->price : null,
            'is_active'   => $this->is_active,
        ];

        // Foto
        if ($this->photo) {
            if ($this->existingPhoto) {
                MediaStorage::delete($this->existingPhoto);
            }
            $data['photo_path'] = MediaStorage::store($this->photo, 'ai-bot/products');
        } elseif ($this->editingId) {
            $data['photo_path'] = $this->existingPhoto;
        }

        // Base de conhecimento (texto colado)
        $data['document_content'] = trim($this->documentText) ?: null;
        $data['document_path'] = $data['document_content'] ? 'text-input' : null;

        if ($this->editingId) {
            AiBotProduct::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: ucfirst($this->type) . ' atualizado com sucesso.');
        } else {
            AiBotProduct::create($data);
            $this->dispatch('toast', type: 'success', message: ucfirst($this->type) . ' cadastrado com sucesso.');
        }

        $this->showForm = false;
        $this->reset(['editingId','name','description','show_price','price','photo','existingPhoto','documentText','existingDocument']);
    }

    public function removeDocument(): void
    {
        if (!$this->editingId) return;
        $item = AiBotProduct::findOrFail($this->editingId);
        if ($item->document_path) {
            MediaStorage::delete($item->document_path);
            $item->update(['document_path' => null, 'document_content' => null]);
        }
        $this->existingDocument = null;
        $this->dispatch('toast', type: 'success', message: 'Documento removido.');
    }

    public function toggleActive(int $id): void
    {
        $item = AiBotProduct::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
    }

    public function delete(int $id): void
    {
        $item = AiBotProduct::findOrFail($id);
        if ($item->photo_path) MediaStorage::delete($item->photo_path);
        if ($item->document_path) MediaStorage::delete($item->document_path);
        $item->delete();
        $this->dispatch('toast', type: 'success', message: 'Item removido.');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['editingId','name','description','show_price','price','photo','existingPhoto','documentText','existingDocument']);
    }

    /**
     * Extrai texto de um arquivo PDF ou TXT.
     */
    private function extractText($file): ?string
    {
        try {
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext === 'txt') {
                return file_get_contents($file->getRealPath());
            }

            if ($ext === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $text = $pdf->getText();
                // Limpa espaços excessivos
                $text = preg_replace('/[ \t]+/', ' ', $text);
                $text = preg_replace('/\n{3,}/', "\n\n", $text);
                return trim($text);
            }

            return null;
        } catch (\Throwable $e) {
            \Log::warning('extractText failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function render()
    {
        $products = AiBotProduct::orderBy('type')->orderBy('name')->get();
        return view('livewire.admin.ai-bot-products', compact('products'));
    }
}
