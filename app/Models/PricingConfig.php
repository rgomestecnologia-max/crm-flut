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

            // Gestão Consultiva
            'consultoria_price'       => '500.00',
            'consultoria_hours'       => '4',
            'consultoria_setup'       => '0.00',

            // Integrações
            'integration_setup'       => '800.00',
            'integration_monthly'     => '200.00',

            // Conteúdo da proposta (PDF)
            'multi_benefits' => "Centralize todo o atendimento WhatsApp da sua empresa em uma única plataforma profissional.\n\n✅ Múltiplos agentes atendendo simultaneamente no mesmo número\n✅ Departamentos organizados (Comercial, Suporte, Financeiro, etc.)\n✅ Chatbot inteligente para triagem automática 24h\n✅ Histórico completo de todas as conversas\n✅ Transferência de atendimento entre agentes e departamentos\n✅ Indicadores de performance por agente\n✅ Respostas rápidas para agilizar o atendimento\n✅ Suporte a múltiplos números de WhatsApp conectados\n\nAumente a produtividade da equipe e nunca mais perca um cliente por falta de atendimento.",

            'crm_benefits' => "Pipeline visual estilo Kanban para acompanhar cada oportunidade de vendas do início ao fechamento.\n\n✅ Visualização completa do funil de vendas em tempo real\n✅ Cards personalizáveis com campos sob medida para seu negócio\n✅ Arraste e solte para mover oportunidades entre etapas\n✅ Histórico de atividades e anotações por card\n✅ Múltiplos pipelines (Comercial, Pós-venda, Suporte, etc.)\n✅ Exportação de dados para Excel\n✅ Filtros avançados e busca inteligente\n✅ Integração direta com o atendimento WhatsApp\n\nTenha controle total sobre suas vendas e não deixe nenhuma oportunidade escapar.",

            'email_benefits' => "Campanhas de email e WhatsApp em massa com agendamento, templates profissionais e relatórios detalhados.\n\n✅ Disparo de email marketing em massa (até 50.000/mês)\n✅ Disparo de mensagens WhatsApp em massa\n✅ Templates personalizáveis com editor visual\n✅ Agendamento de campanhas para data e hora específicas\n✅ Segmentação de contatos por tags e filtros\n✅ Relatórios de abertura, cliques e entregas\n✅ Gestão de descadastros automática\n✅ API oficial do WhatsApp (Meta) para maior segurança\n\nAlcance seus clientes no canal certo, na hora certa, com a mensagem certa.",

            'ia_benefits' => "Inteligência artificial que atende seus clientes 24 horas por dia, 7 dias por semana, com base de conhecimento personalizada.\n\n✅ Atendimento automático 24h via WhatsApp\n✅ Base de conhecimento treinada com informações do seu negócio\n✅ Respostas naturais e contextualizadas\n✅ Transferência automática para humano quando necessário\n✅ Múltiplos fluxos de IA (SDR, SAC, Agendamento, Cobranças, etc.)\n✅ Aprendizado contínuo com feedback dos atendimentos\n✅ Redução drástica no tempo de resposta\n✅ Economia de até 70% nos custos de atendimento\n\nDeixe a IA cuidar das perguntas frequentes enquanto sua equipe foca no que realmente importa.",

            'integration_benefits' => "Conexão inteligente com seus sistemas externos para automatizar processos e eliminar trabalho manual.\n\n✅ Integração com sites e landing pages\n✅ Integração com lojas virtuais e e-commerces\n✅ Integração com sistemas financeiros e ERPs\n✅ Webhooks para receber e enviar dados automaticamente\n✅ API RESTful para integrações customizadas\n✅ Sincronização automática de leads e contatos\n✅ Automação de fluxos entre sistemas\n✅ Suporte técnico dedicado para implementação\n\nConecte o CRM Flut ao ecossistema da sua empresa e automatize tudo.",

            'chat_interno_benefits' => "Canal de comunicação exclusivo da sua equipe, integrado diretamente ao CRM — sem misturar assuntos de trabalho com WhatsApp pessoal.\n\n✅ Chat em tempo real entre agentes, supervisores e gestores\n✅ Conversas privadas (1 a 1) para alinhamentos rápidos\n✅ Notificações instantâneas de novas mensagens\n✅ Histórico completo e pesquisável de todas as conversas\n✅ Envio de arquivos, imagens e documentos internos\n✅ Indicador de mensagens não lidas\n✅ 100% integrado ao painel do CRM — sem precisar de app externo\n✅ Acesso restrito por empresa (cada equipe vê apenas seu chat)\n\nElimine grupos de WhatsApp para assuntos de trabalho. Tenha um canal profissional, seguro e organizado para sua equipe se comunicar sem distrações.",

            'flutchat_benefits' => "Widget de chat inteligente que você incorpora no site da sua empresa com apenas 1 linha de código. Captura leads, qualifica contatos e direciona para o canal certo — tudo automaticamente.\n\n✅ Botão flutuante personalizável (cor, avatar, posição, texto)\n✅ Fluxo de conversa com perguntas ramificadas (nome, WhatsApp, interesse)\n✅ Captura automática de leads com dados salvos na nuvem\n✅ Opção de dropdown (select) ou botões para respostas\n✅ Redirecionamento inteligente para WhatsApp com mensagem pré-preenchida\n✅ Opção de atendimento por IA em tempo real direto no chat do site\n✅ Múltiplos widgets (um para cada site ou landing page)\n✅ Painel de leads capturados com dados do visitante (IP, página, horário)\n✅ Responsivo para mobile e desktop\n✅ Zero dependências — funciona em qualquer site (WordPress, Wix, HTML, etc.)\n\nTransforme visitantes do site em leads qualificados. Cada pessoa que acessa seu site é uma oportunidade — o FlutChat garante que nenhuma escape.",

            'flutzap_benefits' => "Botão flutuante de WhatsApp para o site da sua empresa — muito mais que um simples link. Um formulário inteligente que coleta informações do visitante antes de abrir o WhatsApp, salvando tudo automaticamente na nuvem.\n\n✅ Botão flutuante de WhatsApp personalizado no site\n✅ Formulário de captura antes de abrir a conversa (nome, e-mail, interesse)\n✅ Dados do visitante salvos automaticamente na nuvem (CRM)\n✅ Mensagem pré-preenchida no WhatsApp com os dados coletados\n✅ Painel com histórico de todos os cliques e formulários preenchidos\n✅ Múltiplos botões por site (um para cada página ou setor)\n✅ Design personalizável (cor, ícone, posição, animação)\n✅ Responsivo — funciona perfeitamente em celular e desktop\n✅ Instalação simples com 1 linha de código\n✅ Relatórios de conversão (quantos visitantes clicaram e preencheram)\n\nSaiba exatamente quem está entrando em contato pelo site. Chega de receber \"Oi\" no WhatsApp sem saber de onde veio — com o FlutZap, cada lead chega identificado e registrado.",

            'consultoria_benefits' => "Tenha um especialista Flut dedicado ao sucesso da sua operação — acompanhamento mensal com foco em resultados.\n\n✅ Acompanhamento mensal do CRM e indicadores\n✅ Suporte operacional para sua equipe\n✅ Análise do funil de vendas com insights acionáveis\n✅ Reunião de alinhamento mensal\n✅ Sugestões de melhorias contínuas na operação\n✅ Melhorias contínuas no treinamento e processo de atendimento da IA\n✅ Criação e disparo de campanhas de marketing\n✅ Criação de novas automações sob demanda\n✅ Criação de visões e relatórios customizados\n\n4 horas mensais de consultoria dedicada para garantir que sua empresa extraia o máximo da plataforma.",

            // Screenshots dos módulos (PDF)
            'multi_screenshot' => '',
            'crm_screenshot' => '',
            'email_screenshot' => '',
            'ia_screenshot' => '',
            'integration_screenshot' => '',
            'chat_interno_screenshot' => '',
            'flutchat_screenshot' => '',
            'flutzap_screenshot' => '',
            'consultoria_screenshot' => '',
        ];
    }

    public static function seed(): void
    {
        foreach (self::defaults() as $key => $value) {
            self::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
