/**
 * Oracle X — IA Insights
 * Componente reutilizável para análises de IA em todos os módulos.
 * Painel slide-in com streaming SSE e renderização Markdown.
 */
(function() {
    'use strict';

    // ========== CSS ==========
    const css = document.createElement('style');
    css.textContent = `
.ia-insight-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.35);z-index:9998;opacity:0;pointer-events:none;transition:opacity .3s;backdrop-filter:blur(2px)}
.ia-insight-overlay.open{opacity:1;pointer-events:all}
.ia-insight-panel{position:fixed;top:0;right:-540px;bottom:0;width:520px;max-width:100vw;background:var(--card,#fff);box-shadow:-10px 0 40px rgba(0,0,0,.18);z-index:9999;display:flex;flex-direction:column;transition:right .35s cubic-bezier(.4,0,.2,1);border-left:1px solid rgba(0,0,0,.06)}
.ia-insight-panel.open{right:0}
.ia-insight-header{padding:18px 24px;background:linear-gradient(135deg,#8B5CF6 0%,#6366F1 50%,#4F46E5 100%);color:#fff;display:flex;align-items:center;gap:12px;flex-shrink:0;box-shadow:0 2px 10px rgba(99,102,241,.3)}
.ia-insight-header i.fa-robot{font-size:20px;opacity:.9}
.ia-insight-header h3{margin:0;font-size:15px;font-weight:600;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ia-insight-header .ia-model-badge{font-size:10px;background:rgba(255,255,255,.2);padding:3px 8px;border-radius:20px;font-weight:500}
.ia-insight-close{background:rgba(255,255,255,.15);border:none;color:#fff;width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:background .2s}
.ia-insight-close:hover{background:rgba(255,255,255,.3)}
.ia-insight-body{flex:1;overflow-y:auto;padding:24px;font-size:14px;line-height:1.75;color:var(--gray-700,#374151);scroll-behavior:smooth}
.ia-insight-body::-webkit-scrollbar{width:6px}.ia-insight-body::-webkit-scrollbar-thumb{background:var(--gray-300,#D1D5DB);border-radius:3px}
.ia-insight-body h1,.ia-insight-body h2,.ia-insight-body h3{color:var(--gray-800,#1F2937);margin:1.2em 0 .5em;font-weight:700}
.ia-insight-body h1{font-size:20px}.ia-insight-body h2{font-size:17px}.ia-insight-body h3{font-size:15px}
.ia-insight-body p{margin:.6em 0}
.ia-insight-body ul,.ia-insight-body ol{padding-left:1.5em;margin:.5em 0}
.ia-insight-body li{margin:.3em 0}
.ia-insight-body code{background:var(--gray-100,#F3F4F6);padding:2px 6px;border-radius:4px;font-size:13px;color:#7C3AED}
.ia-insight-body pre{background:#1E1E2E;color:#CDD6F4;padding:16px;border-radius:10px;overflow-x:auto;font-size:13px;margin:1em 0}
.ia-insight-body pre code{background:none;color:inherit;padding:0}
.ia-insight-body table{width:100%;border-collapse:collapse;margin:1em 0;font-size:13px}
.ia-insight-body th,.ia-insight-body td{padding:8px 12px;border:1px solid var(--gray-200,#E5E7EB);text-align:left}
.ia-insight-body th{background:var(--gray-50,#F9FAFB);font-weight:600;color:var(--gray-700,#374151)}
.ia-insight-body blockquote{border-left:4px solid #8B5CF6;margin:1em 0;padding:.5em 1em;background:#F5F3FF;border-radius:0 8px 8px 0;color:#5B21B6}
.ia-insight-body strong{color:var(--gray-800,#1F2937)}
.ia-insight-body em{color:var(--gray-500,#6B7280)}
.ia-insight-body hr{border:none;border-top:1px solid var(--gray-200,#E5E7EB);margin:1.5em 0}
.ia-insight-footer{padding:14px 24px;border-top:1px solid var(--gray-200,#E5E7EB);display:flex;gap:8px;justify-content:flex-end;flex-shrink:0;background:var(--gray-50,#F9FAFB)}
.ia-insight-footer button{padding:7px 16px;border-radius:8px;border:1px solid var(--gray-300,#D1D5DB);background:#fff;cursor:pointer;font-size:13px;color:var(--gray-600,#4B5563);display:inline-flex;align-items:center;gap:6px;transition:all .15s}
.ia-insight-footer button:hover{background:var(--gray-100,#F3F4F6);border-color:var(--gray-400,#9CA3AF)}
.ia-insight-footer button.primary{background:#8B5CF6;color:#fff;border-color:#8B5CF6}
.ia-insight-footer button.primary:hover{background:#7C3AED}
.ia-insight-loading{text-align:center;padding:60px 24px}
.ia-insight-loading .spinner{width:44px;height:44px;border:3px solid var(--gray-200,#E5E7EB);border-top:3px solid #8B5CF6;border-radius:50%;animation:ia-spin .8s linear infinite;margin:0 auto 20px}
.ia-insight-loading p{color:var(--gray-500,#6B7280);font-size:14px;margin:0}
.ia-insight-loading .model-name{font-size:12px;color:var(--gray-400,#9CA3AF);margin-top:8px}
@keyframes ia-spin{to{transform:rotate(360deg)}}
.ia-insight-input-wrap{padding:24px;display:flex;flex-direction:column;gap:14px}
.ia-insight-input-wrap label{font-weight:600;color:var(--gray-700,#374151);font-size:14px}
.ia-insight-input-wrap textarea,.ia-insight-input-wrap input[type="text"]{width:100%;padding:12px 16px;border:2px solid var(--gray-200,#E5E7EB);border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s;resize:vertical;background:var(--card,#fff);color:var(--gray-800,#1F2937)}
.ia-insight-input-wrap textarea:focus,.ia-insight-input-wrap input[type="text"]:focus{border-color:#8B5CF6;box-shadow:0 0 0 3px rgba(139,92,246,.1)}
.ia-insight-input-wrap .ia-submit-btn{padding:12px 24px;background:linear-gradient(135deg,#8B5CF6,#6366F1);color:#fff;border:none;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:opacity .2s,transform .2s}
.ia-insight-input-wrap .ia-submit-btn:hover{opacity:.9;transform:translateY(-1px)}
.ia-insight-btn{background:linear-gradient(135deg,#8B5CF6,#6366F1)!important;color:#fff!important;border:none!important;display:inline-flex!important;align-items:center;gap:6px;font-weight:500;padding:7px 14px;border-radius:8px;cursor:pointer;font-size:13px;transition:opacity .2s,transform .2s;box-shadow:0 2px 8px rgba(99,102,241,.25)}
.ia-insight-btn:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 4px 12px rgba(99,102,241,.35)}
.ia-insight-btn i{font-size:12px}
@media(max-width:640px){.ia-insight-panel{width:100vw;right:-100vw}}
`;
    document.head.appendChild(css);

    // ========== Marked.js dynamic load ==========
    let markedReady = (typeof marked !== 'undefined');
    if (!markedReady) {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        s.onload = () => { markedReady = true; };
        document.head.appendChild(s);
    }

    // ========== Panel elements ==========
    let panel = null, overlay = null, currentController = null, currentContent = '';

    function ensurePanel() {
        if (panel) return;
        overlay = document.createElement('div');
        overlay.className = 'ia-insight-overlay';
        overlay.addEventListener('click', closePanel);
        document.body.appendChild(overlay);

        panel = document.createElement('div');
        panel.className = 'ia-insight-panel';
        panel.innerHTML = `
            <div class="ia-insight-header">
                <i class="fas fa-robot"></i>
                <h3 class="ia-insight-title">Análise IA</h3>
                <span class="ia-model-badge ia-insight-model" style="display:none"></span>
                <button class="ia-insight-close" onclick="iaInsightClose()" title="Fechar"><i class="fas fa-times"></i></button>
            </div>
            <div class="ia-insight-body"></div>
            <div class="ia-insight-footer" style="display:none">
                <button onclick="iaInsightCopy()"><i class="fas fa-copy"></i> Copiar</button>
                <button onclick="iaInsightNewTab()"><i class="fas fa-external-link-alt"></i> Nova aba</button>
                <button class="primary" onclick="iaInsightClose()"><i class="fas fa-check"></i> OK</button>
            </div>`;
        document.body.appendChild(panel);

        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && panel.classList.contains('open')) closePanel();
        });
    }

    function openPanel(title) {
        ensurePanel();
        panel.querySelector('.ia-insight-title').textContent = title || 'Análise IA';
        panel.querySelector('.ia-insight-model').style.display = 'none';
        panel.querySelector('.ia-insight-footer').style.display = 'none';
        panel.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        if (panel) panel.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        document.body.style.overflow = '';
        if (currentController) {
            currentController.abort();
            currentController = null;
        }
    }

    // ========== Title mapping ==========
    const TITLES = {
        'dashboard_briefing':       '🧠 Briefing Diário IA',
        'kb_search':                '🔍 Busca Inteligente',
        'kb_generate':              '📝 Gerar Artigo da KB',
        'relatorios_narrator':      '📊 Narração Executiva',
        'sla_predictive':           '🛡️ Análise SLA Preditiva',
        'monitor_correlation':      '🔗 Correlação de Incidentes',
        'contratos_intelligence':   '📋 Inteligência de Contratos',
        'kanban_assignment':        '👥 Análise de Distribuição',
        'projeto_risk':             '⚠️ Análise de Risco',
        'inventario_lifecycle':     '🔄 Ciclo de Vida dos Ativos',
        'timesheet_productivity':   '⏱️ Análise de Produtividade',
        'financeiro_anomalias':     '💰 Detecção de Anomalias',
        'cmdb_impact':              '🔗 Análise de Impacto',
        'chat_ia':                  '🤖 Assistente IA',
        'deploy_risk':              '🚀 Análise de Deploy',
        'automacoes_suggest':       '⚡ Sugestões de Automação',
    };

    // ========== Main function ==========
    /**
     * Abre o painel de insights e inicia streaming de IA.
     * @param {string} type - Tipo do insight (ex: 'dashboard_briefing')
     * @param {object} data - Dados extras para enviar ao backend
     * @param {object} options - {title, input, inputLabel, inputPlaceholder, inputKey, inputValue, inputType}
     */
    window.iaInsight = function(type, data, options) {
        data = data || {};
        options = options || {};
        const title = options.title || TITLES[type] || '🤖 Análise IA';
        openPanel(title);

        const body = panel.querySelector('.ia-insight-body');

        // Se precisa de input do usuário primeiro
        if (options.input) {
            const isTextarea = options.inputType !== 'text';
            body.innerHTML = `
                <div class="ia-insight-input-wrap">
                    <label>${esc(options.inputLabel || 'Digite sua consulta:')}</label>
                    ${isTextarea
                        ? `<textarea rows="3" id="iaInsightInput" placeholder="${esc(options.inputPlaceholder || '')}">${esc(options.inputValue || '')}</textarea>`
                        : `<input type="text" id="iaInsightInput" placeholder="${esc(options.inputPlaceholder || '')}" value="${esc(options.inputValue || '')}">`
                    }
                    <button class="ia-submit-btn" id="iaInsightSubmitBtn">
                        <i class="fas fa-robot"></i> Analisar com IA
                    </button>
                </div>`;

            // Evento de submit
            const submitFn = function() {
                const input = document.getElementById('iaInsightInput');
                if (!input || !input.value.trim()) return;
                data[options.inputKey || 'query'] = input.value.trim();
                streamInsight(type, data, body);
            };
            document.getElementById('iaInsightSubmitBtn').addEventListener('click', submitFn);
            document.getElementById('iaInsightInput').addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && (e.ctrlKey || options.inputType === 'text')) submitFn();
            });
            setTimeout(() => document.getElementById('iaInsightInput')?.focus(), 150);
            return;
        }

        // Streaming direto
        streamInsight(type, data, body);
    };

    // ========== SSE Streaming ==========
    function streamInsight(type, data, body) {
        body.innerHTML = `
            <div class="ia-insight-loading">
                <div class="spinner"></div>
                <p>Analisando dados com IA...</p>
                <p class="model-name">Conectando ao modelo...</p>
            </div>`;

        currentContent = '';
        currentController = new AbortController();
        const baseUrl = document.getElementById('baseUrl')?.value || '';

        fetch(baseUrl + '/api/ia-insights.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, ...data }),
            signal: currentController.signal
        })
        .then(response => {
            if (!response.ok) throw new Error('Erro HTTP ' + response.status);
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            function processEvents(text) {
                buffer += text;
                const lines = buffer.split('\n');
                buffer = lines.pop(); // Guardar linha incompleta

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const payload = line.substring(6).trim();
                    if (payload === '[DONE]') {
                        finalize(body);
                        return true;
                    }
                    try {
                        const evt = JSON.parse(payload);
                        if (evt.error) {
                            body.innerHTML = `<div style="padding:24px;text-align:center">
                                <i class="fas fa-exclamation-circle" style="font-size:32px;color:#EF4444;margin-bottom:12px;display:block"></i>
                                <p style="color:#EF4444;font-weight:600">${esc(evt.error)}</p></div>`;
                            return true;
                        }
                        if (evt.status === 'connected') {
                            const modelEl = panel.querySelector('.ia-insight-model');
                            if (modelEl && evt.model_label) {
                                modelEl.textContent = evt.model_label;
                                modelEl.style.display = '';
                            }
                            const loadingModel = body.querySelector('.model-name');
                            if (loadingModel) loadingModel.textContent = evt.model_label || evt.model;
                        }
                        if (evt.loading) {
                            const loadingP = body.querySelector('.ia-insight-loading p:first-of-type');
                            if (loadingP) loadingP.textContent = 'IA processando dados...';
                        }
                        if (evt.content) {
                            currentContent += evt.content;
                            renderContent(body, currentContent);
                        }
                    } catch(e) {}
                }
                return false;
            }

            function read() {
                return reader.read().then(({ done, value }) => {
                    if (done) { finalize(body); return; }
                    const text = decoder.decode(value, { stream: true });
                    if (processEvents(text)) return;
                    return read();
                });
            }
            return read();
        })
        .catch(err => {
            if (err.name === 'AbortError') return;
            body.innerHTML = `<div style="padding:24px;text-align:center">
                <i class="fas fa-exclamation-circle" style="font-size:32px;color:#EF4444;margin-bottom:12px;display:block"></i>
                <p style="color:#EF4444;font-weight:600">Erro ao conectar com a IA</p>
                <p style="color:var(--gray-500);font-size:13px;margin-top:8px">${esc(err.message)}</p></div>`;
        });
    }

    function renderContent(body, content) {
        if (markedReady && typeof marked !== 'undefined') {
            try {
                body.innerHTML = marked.parse(content);
            } catch(e) {
                body.textContent = content;
            }
        } else {
            body.innerHTML = content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/\n/g, '<br>');
        }
        // Auto-scroll
        body.scrollTop = body.scrollHeight;
    }

    function finalize(body) {
        renderContent(body, currentContent);
        panel.querySelector('.ia-insight-footer').style.display = 'flex';
        currentController = null;
    }

    // ========== Actions ==========
    window.iaInsightClose = closePanel;

    window.iaInsightCopy = function() {
        if (!currentContent) return;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(currentContent).then(() => {
                if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) HelpDesk.toast('Conteúdo copiado!', 'success');
            });
        } else {
            // Fallback
            const ta = document.createElement('textarea');
            ta.value = currentContent;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
            if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) HelpDesk.toast('Conteúdo copiado!', 'success');
        }
    };

    window.iaInsightNewTab = function() {
        if (!currentContent) return;
        const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>IA Insight - Oracle X</title>
            <style>body{font-family:-apple-system,sans-serif;max-width:800px;margin:40px auto;padding:0 20px;color:#1F2937;line-height:1.7}
            h1,h2,h3{color:#4F46E5}table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #E5E7EB;text-align:left}th{background:#F9FAFB}
            code{background:#F3F4F6;padding:2px 6px;border-radius:4px}pre{background:#1E1E2E;color:#CDD6F4;padding:16px;border-radius:8px;overflow-x:auto}
            blockquote{border-left:4px solid #8B5CF6;padding:.5em 1em;background:#F5F3FF;margin:1em 0}</style></head>
            <body>${markedReady && typeof marked !== 'undefined' ? marked.parse(currentContent) : currentContent.replace(/\n/g,'<br>')}</body></html>`;
        const blob = new Blob([html], { type: 'text/html' });
        window.open(URL.createObjectURL(blob), '_blank');
    };

    // ========== Helpers ==========
    function esc(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
})();
