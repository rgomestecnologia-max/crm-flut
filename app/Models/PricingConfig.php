<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingConfig extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::where('key', $key)->value('value');
        return $value !== null ? $value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
    }

    public static function getAll(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    public static function defaults(): array
    {
        return [
            // Multi-atendimento
            'multi_base_price'        => '349.90',
            'multi_base_users'        => '3',
            'multi_extra_user'        => '49.00',
            'multi_extra_instance'    => '189.90',
            'multi_setup'             => '600.00',

            // CRM
            'crm_price'               => '349.90',
            'crm_setup'               => '350.00',

            // Disparos Email
            'email_5k_price'          => '200.00',
            'email_20k_price'         => '400.00',
            'email_50k_price'         => '750.00',
            'email_setup'             => '300.00',

            // IA
            'ia_flow_price'           => '499.00',
            'ia_flow_setup'           => '500.00',

            // Chat Interno
            'chat_interno_price'      => '149.00',
            'chat_interno_setup'      => '300.00',

            // FlutChat
            'flutchat_price'          => '199.00',
            'flutchat_ia_price'       => '349.00',
            'flutchat_setup'          => '400.00',

            // FlutZap
            'flutzap_price'           => '249.00',
            'flutzap_setup'           => '400.00',

            // Integrações
            'integration_setup'       => '800.00',
            'integration_monthly'     => '200.00',

            // Conteúdo da proposta (PDF)
            'multi_benefits' => "Centralize todo o atendimento WhatsApp da sua empresa em uma única plataforma profissional.\n\n✅ Múltiplos agentes atendendo simultaneamente no mesmo número\n✅ Departamentos organizados (Comercial, Suporte, Financeiro, etc.)\n✅ Chatbot inteligente para triagem automática 24h\n✅ Histórico completo de todas as conversas\n✅ Transferência de atendimento entre agentes e departamentos\n✅ Indicadores de performance por agente\n✅ Respostas rápidas para agilizar o atendimento\n✅ Suporte a múltiplos números de WhatsApp conectados\n\nAumente a produtividade da equipe e nunca mais perca um cliente por falta de atendimento.",

            'crm_benefits' => "Pipeline visual estilo Kanban para acompanhar cada oportunidade de vendas do início ao fechamento.\n\n✅ Visualização completa do funil de vendas em tempo real\n✅ Cards personalizáveis com campos sob medida para seu negócio\n✅ Arraste e solte para mover oportunidades entre etapas\n✅ Histórico de atividades e anotações por card\n✅ Múltiplos pipelines (Comercial, Pós-venda, Suporte, etc.)\n✅ Exportação de dados para Excel\n✅ Filtros avançados e busca inteligente\n✅ Integração direta com o atendimento WhatsApp\n\nTenha controle total sobre suas vendas e não deixe nenhuma oportunidade escapar.",

            'email_benefits' => "Campanhas de email e WhatsApp em massa com agendamento, templates profissionais e relatórios detalhados.\n\n✅ Disparo de email marketing em massa (até 50.000/mês)\n✅ Disparo de mensagens WhatsApp em massa\n✅ Templates personalizáveis com editor visual\n✅ Agendamento de campanhas para data e hora específicas\n✅ Segmentação de contatos por tags e filtros\n✅ Relatórios de abertura, cliques e entregas\n✅ Gestão de descadastros automática\n✅ API oficial do WhatsApp (Meta) para maior segurança\n\nAlcance seus clientes no canal certo, na hora certa, com a mensagem certa.",

            'ia_benefits' => "Inteligência artificial que atende seus clientes 24 horas por dia, 7 dias por semana, com base de conhecimento personalizada.\n\n✅ Atendimento automático 24h via WhatsApp\n✅ Base de conhecimento treinada com informações do seu negócio\n✅ Respostas naturais e contextualizadas\n✅ Transferência automática para humano quando necessário\n✅ Múltiplos fluxos de IA (SDR, SAC, Agendamento, Cobranças, etc.)\n✅ Aprendizado contínuo com feedback dos atendimentos\n✅ Redução drástica no tempo de resposta\n✅ Economia de até 70% nos custos de atendimento\n\nDeixe a IA cuidar das perguntas frequentes enquanto sua equipe foca no que realmente importa.",

            'integration_benefits' => "Conexão inteligente com seus sistemas externos para automatizar processos e eliminar trabalho manual.\n\n✅ Integração com sites e landing pages\n✅ Integração com lojas virtuais e e-commerces\n✅ Integração com sistemas financeiros e ERPs\n✅ Webhooks para receber e enviar dados automaticamente\n✅ API RESTful para integrações customizadas\n✅ Sincronização automática de leads e contatos\n✅ Automação de fluxos entre sistemas\n✅ Suporte técnico dedicado para implementação\n\nConecte o CRM Flut ao ecossistema da sua empresa e automatize tudo.",

            'chat_interno_benefits' => "Comunicação interna integrada para sua equipe, sem depender de WhatsApp pessoal.\n\n✅ Chat em tempo real entre agentes e supervisores\n✅ Mensagens privadas e em grupo\n✅ Notificações de novas mensagens\n✅ Histórico completo de conversas internas\n✅ Integrado ao painel do CRM\n\nMantenha a comunicação da equipe organizada e profissional.",

            'flutchat_benefits' => "Widget de chat incorporável para o site da sua empresa, com fluxo de perguntas e captura de leads.\n\n✅ Widget flutuante personalizável (cor, avatar, posição)\n✅ Fluxo de conversa com perguntas ramificadas\n✅ Captura automática de leads (nome, WhatsApp, e-mail)\n✅ Redirecionamento para WhatsApp com mensagem pré-preenchida\n✅ Opção de atendimento por IA em tempo real\n✅ Múltiplos widgets por empresa\n✅ Responsivo para mobile e desktop\n\nCapture leads direto do site e direcione para o canal certo.",

            'flutzap_benefits' => "Automação avançada de WhatsApp com disparos programados, follow-ups e integração com CRM.\n\n✅ Disparos de WhatsApp em massa com agendamento\n✅ Follow-up automático para leads sem resposta\n✅ Confirmação de agendamentos com respostas SIM/NÃO\n✅ Integração com pipeline CRM (move cards automaticamente)\n✅ Variações de mensagem com IA para evitar bloqueios\n✅ Relatórios de entrega e leitura\n\nAutomatize sua comunicação e nunca perca um follow-up.",

            // Screenshots dos módulos (PDF)
            'multi_screenshot' => '',
            'crm_screenshot' => '',
            'email_screenshot' => '',
            'ia_screenshot' => '',
            'integration_screenshot' => '',
            'chat_interno_screenshot' => '',
            'flutchat_screenshot' => '',
            'flutzap_screenshot' => '',
        ];
    }

    public static function seed(): void
    {
        foreach (self::defaults() as $key => $value) {
            self::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
