<?php if (!isLoggedIn()) redirect('login.php'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Configurações do Chatbot</h3>
                </div>
                <div class="card-body">
                    <form id="formConfigChatbot">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status do Chatbot</label>
                                    <select name="chatbot_ativo" class="form-control">
                                        <option value="1">Ativo</option>
                                        <option value="0">Inativo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nome do Assistente</label>
                                    <input type="text" name="chatbot_nome" class="form-control" placeholder="Ex: Oracle Bot">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Mensagem de Boas-vindas</label>
                            <textarea name="chatbot_boas_vindas" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Mensagem de Sem Resposta</label>
                            <textarea name="chatbot_sem_resposta" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Modelo de IA</label>
                                    <select name="chatbot_modelo_ia" class="form-control">
                                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                        <option value="gpt-4">GPT-4</option>
                                        <option value="local">Modelo Local</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Max Tokens (Resposta)</label>
                                    <input type="number" name="chatbot_max_tokens" class="form-control" value="150">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formConfigChatbot');

    // Carregar configurações ao abrir
    HelpDesk.api('GET', '/api/chatbot.php')
        .then(response => {
            if (response.status === 'success' && response.data) {
                const data = response.data;
                // Preencher os campos do formulário
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
            }
        })
        .catch(err => {
            console.error('Erro ao carregar configs:', err);
            HelpDesk.toast('Erro ao carregar configurações.', 'error');
        });

    // Salvar configurações
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Serializar formulário
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        HelpDesk.api('POST', '/api/chatbot.php', data)
            .then(response => {
                if (response.status === 'success') {
                    HelpDesk.toast('Configurações salvas com sucesso!', 'success');
                } else {
                    HelpDesk.toast(response.message || 'Erro ao salvar.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                HelpDesk.toast('Erro na comunicação com o servidor.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    });
});
</script>