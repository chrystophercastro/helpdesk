<?php
/**
 * View: Permissões de Módulos
 * Admin-only: gerenciar acesso de usuários aos módulos
 */
if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/index.php?page=dashboard');
    exit;
}
?>

<style>
.perm-header{margin-bottom:24px}
.perm-header h1{font-size:24px;font-weight:700;color:var(--gray-900);display:flex;align-items:center;gap:12px}
.perm-header h1 i{color:#6366F1}
.perm-header p{color:var(--gray-500);margin-top:4px;font-size:14px}

.perm-modules{display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:24px}
.perm-module-card{background:#fff;border-radius:var(--radius-lg);border:1px solid #f0f0f0;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.perm-module-header{padding:20px 24px;display:flex;align-items:center;gap:16px;border-bottom:1px solid #f1f5f9}
.perm-module-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff}
.perm-module-info h3{font-size:16px;font-weight:700;color:var(--gray-900);margin:0}
.perm-module-info p{font-size:13px;color:var(--gray-500);margin:0}
.perm-module-body{padding:16px 24px}

.perm-user-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f8fafc}
.perm-user-row:last-child{border-bottom:none}
.perm-user-left{display:flex;align-items:center;gap:10px}
.perm-user-avatar{width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.perm-user-name{font-size:14px;font-weight:600;color:var(--gray-800)}
.perm-user-email{font-size:12px;color:var(--gray-400)}
.perm-user-right{display:flex;align-items:center;gap:8px}
.perm-nivel-select{padding:6px 10px;border:1px solid #e2e8f0;border-radius:var(--radius);font-size:13px;background:#fff}
.perm-toggle{width:40px;height:22px;border-radius:11px;border:none;cursor:pointer;position:relative;transition:background .2s}
.perm-toggle.on{background:#10B981}
.perm-toggle.off{background:#E2E8F0}
.perm-toggle::after{content:'';position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:50%;background:#fff;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.15)}
.perm-toggle.on::after{transform:translateX(18px)}

.perm-search{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:var(--radius);font-size:13px;margin-bottom:12px}
.perm-search:focus{outline:none;border-color:var(--primary)}
.perm-count{font-size:12px;color:var(--gray-400);margin-bottom:8px}

.perm-grupo{margin-bottom:32px}
.perm-grupo-title{font-size:18px;font-weight:700;color:var(--text-primary,#1E293B);margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid var(--border,#E2E8F0)}
.perm-grupo-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px}

@media(max-width:768px){.perm-modules{grid-template-columns:1fr}.perm-grupo-grid{grid-template-columns:1fr}}
</style>

<div class="perm-header">
    <h1><i class="fas fa-shield-alt"></i> Permissões de Módulos</h1>
    <p>Gerencie o acesso individual dos usuários a cada módulo do sistema. Admin sempre tem acesso total.</p>
</div>

<div class="perm-modules" id="permModules">
    <div style="text-align:center;padding:40px;color:var(--gray-400)"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
</div>

<script>
const PERM_API = '<?= BASE_URL ?>/api/modulos.php';

function showToast(msg, type = 'info') {
    const typeMap = { error: 'danger', success: 'success', warning: 'warning', info: 'info' };
    if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) {
        HelpDesk.toast(msg, typeMap[type] || type);
    }
}

// Full module metadata – matches ModuloPermissao::getModulosDisponiveis()
const moduloMeta = {
    chatbot:          { nome: 'Chatbot',             icone: 'fas fa-headset',            cor: '#06B6D4',  grupo: 'Inteligência Artificial' },
    compras:          { nome: 'Compras',             icone: 'fas fa-shopping-cart',       cor: '#F59E0B',  grupo: 'Operações TI' },
    inventario:       { nome: 'Inventário',          icone: 'fas fa-boxes',               cor: '#8B5CF6',  grupo: 'Operações TI' },
    suprimentos:      { nome: 'Suprimentos',         icone: 'fas fa-warehouse',           cor: '#8B5CF6',  grupo: 'Operações TI' },
    remoto:           { nome: 'Acesso Remoto',       icone: 'fas fa-desktop',             cor: '#3B82F6',  grupo: 'Ferramentas' },
    senhas:           { nome: 'Cofre de Senhas',     icone: 'fas fa-key',                 cor: '#EF4444',  grupo: 'Ferramentas' },
    rede:             { nome: 'Gestão de Rede',      icone: 'fas fa-server',              cor: '#3B82F6',  grupo: 'Ferramentas' },
    ssh:              { nome: 'Terminal SSH',         icone: 'fas fa-terminal',            cor: '#10B981',  grupo: 'Ferramentas' },
    proxmox:          { nome: 'Proxmox VE',          icone: 'fas fa-cloud',               cor: '#E97521',  grupo: 'Ferramentas' },
    mikrotik:         { nome: 'MikroTik',            icone: 'fas fa-network-wired',       cor: '#D6336C',  grupo: 'Ferramentas' },
    github:           { nome: 'GitHub',              icone: 'fab fa-github',              cor: '#6E40C9',  grupo: 'Ferramentas' },
    cmdb:             { nome: 'CMDB',                icone: 'fas fa-sitemap',             cor: '#06B6D4',  grupo: 'Ferramentas' },
    monitor:          { nome: 'Monitor NOC',         icone: 'fas fa-heartbeat',           cor: '#EF4444',  grupo: 'Ferramentas' },
    airflow:          { nome: 'Airflow',             icone: 'fas fa-wind',                cor: '#017CEE',  grupo: 'Ferramentas' },
    folha_pagamento:  { nome: 'Folha de Pagamento',  icone: 'fas fa-money-check-alt',     cor: '#10B981',  grupo: 'Financeiro / RH' },
    financeiro:       { nome: 'Financeiro',          icone: 'fas fa-file-invoice-dollar',  cor: '#F59E0B',  grupo: 'Financeiro / RH' },
    sla:              { nome: 'SLA Dashboard',       icone: 'fas fa-tachometer-alt',       cor: '#F59E0B',  grupo: 'Gestão' },
    contratos:        { nome: 'Contratos',           icone: 'fas fa-file-contract',        cor: '#8B5CF6',  grupo: 'Gestão' },
    automacoes:       { nome: 'Automações',          icone: 'fas fa-robot',                cor: '#10B981',  grupo: 'Gestão' },
    usuarios:         { nome: 'Usuários',            icone: 'fas fa-users-cog',            cor: '#3B82F6',  grupo: 'Administração' },
    relatorios:       { nome: 'Relatórios',          icone: 'fas fa-chart-bar',            cor: '#8B5CF6',  grupo: 'Gestão' },
    departamentos:    { nome: 'Departamentos',       icone: 'fas fa-building',             cor: '#6366F1',  grupo: 'Administração' },
    ad:               { nome: 'Active Directory',    icone: 'fas fa-network-wired',        cor: '#3B82F6',  grupo: 'Administração' },
    configuracoes:    { nome: 'Configurações',       icone: 'fas fa-cog',                  cor: '#64748B',  grupo: 'Administração' },
    atualizacao:      { nome: 'Atualização',         icone: 'fas fa-cloud-upload-alt',     cor: '#F59E0B',  grupo: 'Administração' },
    deploy:           { nome: 'Deploy',              icone: 'fas fa-rocket',               cor: '#6366F1',  grupo: 'Administração' }
};

async function permCarregar() {
    const container = document.getElementById('permModules');
    try {
        const rm = await fetch(PERM_API + '?action=modulos');
        const jm = await rm.json();
        if (!jm.success) return;
        const modulos = Object.keys(jm.data);

        // Group modules by grupo
        const grupos = {};
        for (const modulo of modulos) {
            const meta = moduloMeta[modulo] || { nome: modulo, icone: 'fas fa-puzzle-piece', cor: '#6B7280', grupo: 'Outros' };
            const grupo = meta.grupo || 'Outros';
            if (!grupos[grupo]) grupos[grupo] = [];
            grupos[grupo].push(modulo);
        }

        let html = '';
        for (const [grupo, mods] of Object.entries(grupos)) {
            html += `<div class="perm-grupo"><h2 class="perm-grupo-title">${grupo}</h2><div class="perm-grupo-grid">`;
            for (const modulo of mods) {
                const meta = moduloMeta[modulo] || { nome: modulo, icone: 'fas fa-puzzle-piece', cor: '#6B7280' };
                const ru = await fetch(PERM_API + '?action=listar_usuarios&modulo=' + modulo);
                const ju = await ru.json();
                const usuarios = ju.data || [];

            const usersHtml = usuarios.map(u => {
                const initials = (u.nome || 'XX').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
                const temAcesso = !!u.acesso;
                const nivel = u.acesso || 'leitura';
                const isAdmin = u.tipo === 'admin';

                return `<div class="perm-user-row" data-nome="${(u.nome || '').toLowerCase()}">
                    <div class="perm-user-left">
                        <div class="perm-user-avatar" style="background:${temAcesso ? meta.cor : '#CBD5E1'}">${initials}</div>
                        <div>
                            <div class="perm-user-name">${u.nome || '-'}${isAdmin ? ' <span style="font-size:11px;color:var(--primary)">(Admin)</span>' : ''}</div>
                            <div class="perm-user-email">${u.email || ''}</div>
                        </div>
                    </div>
                    <div class="perm-user-right">
                        ${isAdmin ? '<span style="font-size:12px;color:var(--gray-400)">Acesso total</span>' : `
                            <select class="perm-nivel-select" id="nivel_${modulo}_${u.id}" ${!temAcesso ? 'disabled' : ''} onchange="permAtualizarNivel('${modulo}', ${u.id})">
                                <option value="leitura" ${nivel === 'leitura' ? 'selected' : ''}>Leitura</option>
                                <option value="escrita" ${nivel === 'escrita' ? 'selected' : ''}>Escrita</option>
                                <option value="admin" ${nivel === 'admin' ? 'selected' : ''}>Admin</option>
                            </select>
                            <button class="perm-toggle ${temAcesso ? 'on' : 'off'}" id="toggle_${modulo}_${u.id}"
                                onclick="permToggle('${modulo}', ${u.id}, this)"></button>
                        `}
                    </div>
                </div>`;
            }).join('');

            const ativos = usuarios.filter(u => u.acesso).length;

            html += `<div class="perm-module-card">
                <div class="perm-module-header">
                    <div class="perm-module-icon" style="background:${meta.cor}"><i class="${meta.icone}"></i></div>
                    <div class="perm-module-info">
                        <h3>${meta.nome}</h3>
                        <p>${ativos} de ${usuarios.length} com acesso</p>
                    </div>
                </div>
                <div class="perm-module-body">
                    <input type="text" class="perm-search" placeholder="Filtrar usuários..." oninput="permFiltrar(this)">
                    ${usersHtml}
                </div>
            </div>`;
            }
            html += '</div></div>'; // close perm-grupo-grid + perm-grupo
        }

        container.innerHTML = html;
    } catch(e) { console.error(e); container.innerHTML = '<div style="text-align:center;padding:40px;color:#DC2626">Erro ao carregar permissões</div>'; }
}

async function permToggle(modulo, userId, btn) {
    const isOn = btn.classList.contains('on');
    const nivelSel = document.getElementById('nivel_' + modulo + '_' + userId);

    if (isOn) {
        // Revogar
        try {
            const r = await fetch(PERM_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'revogar', usuario_id: userId, modulo })
            });
            const j = await r.json();
            if (j.success) {
                btn.classList.remove('on');
                btn.classList.add('off');
                if (nivelSel) nivelSel.disabled = true;
                showToast('Acesso revogado', 'success');
            } else showToast(j.error, 'error');
        } catch(e) { showToast('Erro', 'error'); }
    } else {
        // Conceder
        const nivel = nivelSel ? nivelSel.value : 'leitura';
        try {
            const r = await fetch(PERM_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'conceder', usuario_id: userId, modulo, nivel })
            });
            const j = await r.json();
            if (j.success) {
                btn.classList.remove('off');
                btn.classList.add('on');
                if (nivelSel) nivelSel.disabled = false;
                showToast('Acesso concedido', 'success');
            } else showToast(j.error, 'error');
        } catch(e) { showToast('Erro', 'error'); }
    }
}

async function permAtualizarNivel(modulo, userId) {
    const nivel = document.getElementById('nivel_' + modulo + '_' + userId).value;
    try {
        const r = await fetch(PERM_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'conceder', usuario_id: userId, modulo, nivel })
        });
        const j = await r.json();
        if (j.success) showToast('Nível atualizado para ' + nivel, 'success');
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

function permFiltrar(input) {
    const busca = input.value.toLowerCase();
    const card = input.closest('.perm-module-body');
    card.querySelectorAll('.perm-user-row').forEach(row => {
        const nome = row.getAttribute('data-nome') || '';
        row.style.display = nome.includes(busca) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', permCarregar);
</script>
