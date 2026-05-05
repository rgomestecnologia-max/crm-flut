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
    public         $document     = null;
    public string  $documentText = '';
    public ?string $existingDocument = null;

    public function openCreate(): void
    {
        $this->reset(['editingId','name','description','show_price','price','photo','existingPhoto','document','documentText','existingDocument']);
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
            'type'        => 'required|in:produto,servico,documento',
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'show_price'  => 'boolean',
            'price'       => 'nullable|numeric|min:0',
            'photo'       => 'nullable|image|max:4096',
            'document'     => 'nullable|file|mimes:pdf|max:10240',
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

        // Upload PDF (arquivo para enviar ao cliente + extrai texto)
        if ($this->document) {
            if ($this->existingDocument && $this->existingDocument !== 'text-input') {
                MediaStorage::delete($this->existingDocument);
            }
            $data['document_path'] = MediaStorage::store($this->document, 'ai-bot/documents');

            // Extrai texto do PDF para a base de conhecimento (se textarea vazio)
            if (!trim($this->documentText)) {
                $extracted = $this->extractText($this->document);
                if ($extracted) {
                    $this->documentText = $extracted;
                }
            }
        } elseif ($this->editingId) {
            $data['document_path'] = $this->existingDocument;
        }

        // Base de conhecimento (texto colado ou extraído do PDF)
        $data['document_content'] = trim($this->documentText) ?: null;

        if ($this->editingId) {
            AiBotProduct::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: ucfirst($this->type) . ' atualizado com sucesso.');
        } else {
            AiBotProduct::create($data);
            $this->dispatch('toast', type: 'success', message: ucfirst($this->type) . ' cadastrado com sucesso.');
        }

        $this->showForm = false;
        $this->reset(['editingId','name','description','show_price','price','photo','existingPhoto','document','documentText','existingDocument']);
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
        $this->reset(['editingId','name','description','show_price','price','photo','existingPhoto','document','documentText','existingDocument']);
    }

    // ── Upload em lote ─────────────────────────────────────────────────
    public $batchFiles = [];

    public function uploadBatch(): void
    {
        $this->validate([
            'batchFiles'   => 'required|array|min:1',
            'batchFiles.*' => 'file|max:102400|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp,gif',
        ]);

        $uploaded = 0;

        foreach ($this->batchFiles as $file) {
            $ext      = strtolower($file->getClientOriginalExtension());
            $filename = $file->getClientOriginalName();
            $mime     = $file->getMimeType();
            $isImage  = str_starts_with($mime, 'image/');

            $data = [
                'type'      => 'documento',
                'name'      => pathinfo($filename, PATHINFO_FILENAME),
                'is_active' => true,
            ];

            if ($isImage) {
                // Comprime imagem antes de salvar
                $compressed = $this->compressImage($file);
                if ($compressed) {
                    $path = 'ai-bot/products/' . uniqid() . '.jpg';
                    MediaStorage::put($path, $compressed);
                    $data['photo_path'] = $path;
                } else {
                    $data['photo_path'] = MediaStorage::store($file, 'ai-bot/products');
                }
            } else {
                // Documento: salva arquivo + extrai texto
                $data['document_path'] = MediaStorage::store($file, 'ai-bot/documents');
                $extracted = $this->extractText($file);
                if ($extracted) {
                    $data['document_content'] = mb_substr($extracted, 0, 50000);
                }
            }

            AiBotProduct::create($data);
            $uploaded++;
        }

        $this->batchFiles = [];
        $this->dispatch('toast', type: 'success', message: "{$uploaded} arquivo(s) enviado(s) com sucesso.");
    }

    /**
     * Comprime imagem para max 1200px de largura, qualidade 70%.
     */
    private function compressImage($file): ?string
    {
        try {
            $path = $file->getRealPath();
            $info = getimagesize($path);
            if (!$info) return null;

            $mime = $info['mime'];
            $img = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($path),
                'image/png'  => imagecreatefrompng($path),
                'image/webp' => imagecreatefromwebp($path),
                'image/gif'  => imagecreatefromgif($path),
                default      => null,
            };

            if (!$img) return null;

            $width  = imagesx($img);
            $height = imagesy($img);
            $maxW   = 1200;

            if ($width > $maxW) {
                $newH = (int) ($height * ($maxW / $width));
                $resized = imagecreatetruecolor($maxW, $newH);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxW, $newH, $width, $height);
                imagedestroy($img);
                $img = $resized;
            }

            ob_start();
            imagejpeg($img, null, 70);
            $binary = ob_get_clean();
            imagedestroy($img);

            return $binary;
        } catch (\Throwable $e) {
            \Log::warning('compressImage failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extrai texto de PDF, TXT, DOCX ou XLSX.
     */
    private function extractText($file): ?string
    {
        try {
            $ext = strtolower($file->getClientOriginalExtension());
            $path = $file->getRealPath();

            if ($ext === 'txt') {
                return file_get_contents($path);
            }

            if ($ext === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                $text = $pdf->getText();
                $text = preg_replace('/[ \t]+/', ' ', $text);
                $text = preg_replace('/\n{3,}/', "\n\n", $text);
                $text = mb_convert_encoding(trim($text), 'UTF-8', 'UTF-8');
                $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
                return $text;
            }

            // DOCX: extrai texto dos XMLs internos
            if (in_array($ext, ['docx', 'doc'])) {
                return $this->extractFromDocx($path);
            }

            // XLSX/XLS: extrai texto das células
            if (in_array($ext, ['xlsx', 'xls'])) {
                return $this->extractFromXlsx($path);
            }

            return null;
        } catch (\Throwable $e) {
            \Log::warning('extractText failed', ['ext' => $ext ?? '?', 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractFromDocx(string $path): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return null;

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!$xml) return null;

        // Remove XML tags, mantém texto
        $text = strip_tags($xml);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text) ?: null;
    }

    private function extractFromXlsx(string $path): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return null;

        $lines = [];

        // Lê shared strings
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        $strings = [];
        if ($sharedXml) {
            preg_match_all('/<t[^>]*>([^<]+)<\/t>/u', $sharedXml, $matches);
            $strings = $matches[1] ?? [];
        }

        // Lê sheet1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml && !empty($strings)) {
            preg_match_all('/<c[^>]*t="s"[^>]*><v>(\d+)<\/v><\/c>/u', $sheetXml, $matches);
            foreach ($matches[1] ?? [] as $idx) {
                if (isset($strings[(int)$idx])) {
                    $lines[] = $strings[(int)$idx];
                }
            }
        }

        // Também pega valores numéricos
        if ($sheetXml) {
            preg_match_all('/<c[^>]*(?!t="s")[^>]*><v>([^<]+)<\/v><\/c>/u', $sheetXml, $matches);
            foreach ($matches[1] ?? [] as $val) {
                $lines[] = $val;
            }
        }

        return !empty($lines) ? implode("\n", $lines) : null;
    }

    public function render()
    {
        $products = AiBotProduct::orderBy('type')->orderBy('name')->get();
        return view('livewire.admin.ai-bot-products', compact('products'));
    }
}
