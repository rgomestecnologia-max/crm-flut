<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\EvolutionApiConfig;
use App\Services\EvolutionApiService;
use App\Services\ZapiService;
use Illuminate\Console\Command;

class FetchContactAvatars extends Command
{
    protected $signature   = 'contacts:fetch-avatars {--all : Atualiza mesmo quem já tem foto}';
    protected $description = 'Busca fotos de perfil do WhatsApp para contatos sem avatar';

    public function handle(ZapiService $zapi): int
    {
        $query = Contact::query();

        if (!$this->option('all')) {
            $query->whereNull('avatar_url');
        }

        $contacts = $query->get();
        $total    = $contacts->count();

        if ($total === 0) {
            $this->info('Nenhum contato para atualizar.');
            return self::SUCCESS;
        }

        $evolutionConfig = EvolutionApiConfig::current();
        $useEvolution    = $evolutionConfig && $evolutionConfig->is_active;
        $evolutionSvc    = $useEvolution ? new EvolutionApiService($evolutionConfig) : null;

        $this->info("Buscando fotos para {$total} contatos via " . ($useEvolution ? 'Evolution API' : 'Z-API') . '...');
        $bar     = $this->output->createProgressBar($total);
        $updated = 0;

        foreach ($contacts as $contact) {
            try {
                $photo = $useEvolution
                    ? $evolutionSvc->getProfilePicture($contact->phone)
                    : $zapi->getProfilePicture($contact->phone);
                if ($photo) {
                    $contact->update(['avatar_url' => $photo]);
                    $updated++;
                }
            } catch (\Throwable $e) {
                // ignora erros individuais
            }

            $bar->advance();
            usleep(300_000); // 300ms entre requisições para não sobrecarregar o Z-API
        }

        $bar->finish();
        $this->newLine();
        $this->info("Concluído: {$updated}/{$total} contatos atualizados.");

        return self::SUCCESS;
    }
}
