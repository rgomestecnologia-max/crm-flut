<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Session;

/**
 * Singleton que mantém referência da empresa "ativa" durante a request.
 *
 * Estratégia:
 *  - Usuário admin escolhe a empresa em /select-company → grava na sessão.
 *  - Usuário agente/supervisor tem company_id fixo → middleware seta automaticamente.
 *  - Em jobs (sem sessão), use set() explicitamente passando o id que veio do dispatch.
 *
 * IMPORTANTE: este service NÃO bate no banco a cada chamada de id() — ele faz cache em
 * propriedade. O modelo só é carregado se alguém pedir model().
 */
class CurrentCompany
{
    public const SESSION_KEY = 'current_company_id';

    protected ?int     $cachedId    = null;
    protected ?Company $cachedModel = null;

    public function __construct()
    {
        // Bootstrap inicial: lê da sessão se existir.
        if (Session::has(self::SESSION_KEY)) {
            $this->cachedId = (int) Session::get(self::SESSION_KEY);
        }
    }

    /**
     * ID da empresa ativa, ou null se nenhuma foi definida ainda.
     */
    public function id(): ?int
    {
        return $this->cachedId;
    }

    /**
     * Verifica se há uma empresa selecionada na sessão atual.
     */
    public function check(): bool
    {
        return $this->cachedId !== null;
    }

    /**
     * Modelo Company carregado lazy. Cuidado: bate no banco na primeira chamada.
     */
    public function model(): ?Company
    {
        if (!$this->check()) {
            return null;
        }

        if ($this->cachedModel === null) {
            $this->cachedModel = Company::find($this->cachedId);
        }

        return $this->cachedModel;
    }

    /**
     * Define a empresa ativa.
     *
     * @param  int|Company  $company
     * @param  bool         $persist  Se true (default), grava na sessão também.
     */
    public function set(int|Company $company, bool $persist = true): void
    {
        if ($company instanceof Company) {
            $this->cachedId    = $company->id;
            $this->cachedModel = $company;
        } else {
            $this->cachedId    = $company;
            $this->cachedModel = null;
        }

        if ($persist) {
            Session::put(self::SESSION_KEY, $this->cachedId);
        }
    }

    /**
     * Limpa a empresa atual da sessão e do cache.
     */
    public function clear(): void
    {
        $this->cachedId    = null;
        $this->cachedModel = null;
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Executa um callback como se estivesse logado em outra empresa.
     * Restaura a empresa anterior ao final, mesmo se o callback lançar.
     *
     * Usado para operações cross-tenant feitas pelo admin (ex: criar uma empresa
     * nova e seedar template mínimo) sem afetar a sessão da empresa onde ele estava.
     *
     * NÃO toca na sessão (persist=false), só no cache em memória.
     */
    public function asTenant(int $companyId, callable $callback): mixed
    {
        $previousId    = $this->cachedId;
        $previousModel = $this->cachedModel;

        try {
            $this->cachedId    = $companyId;
            $this->cachedModel = null;
            return $callback();
        } finally {
            $this->cachedId    = $previousId;
            $this->cachedModel = $previousModel;
        }
    }
}
