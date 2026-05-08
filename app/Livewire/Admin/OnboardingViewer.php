<?php

namespace App\Livewire\Admin;

use App\Services\MediaStorage;
use Livewire\Component;

class OnboardingViewer extends Component
{
    public ?string $expandedFile = null;

    public function toggle(string $file): void
    {
        $this->expandedFile = $this->expandedFile === $file ? null : $file;
    }

    public function delete(string $file): void
    {
        MediaStorage::delete($file);
        $this->expandedFile = null;
    }

    public function render()
    {
        $disk = MediaStorage::disk();
        $files = collect($disk->files('onboarding'))
            ->filter(fn($f) => str_ends_with($f, '.json'))
            ->sortByDesc(fn($f) => $disk->lastModified($f))
            ->values();

        $submissions = $files->map(function ($file) use ($disk) {
            $data = json_decode($disk->get($file), true);
            $data['_file'] = $file;
            return $data;
        });

        $expandedData = null;
        if ($this->expandedFile) {
            $content = $disk->get($this->expandedFile);
            $expandedData = $content ? json_decode($content, true) : null;
        }

        return view('livewire.admin.onboarding-viewer', compact('submissions', 'expandedData'));
    }
}
