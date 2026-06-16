(function () {
  const form = document.querySelector('[data-simulator-form]');
  const resultBox = document.querySelector('[data-result-box]');
  if (!form || !resultBox) return;

  const stateSelect = form.querySelector('[name="state"]');
  const message = form.querySelector('[data-form-message]');
  const submitBtn = form.querySelector('button[type="submit"]');

  const fmtMoney = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
  const fmtNumber = (value, decimals = 1) => Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

  function setMessage(type, text) {
    if (!message) return;
    message.className = 'form-message ' + type;
    message.textContent = text;
  }

  function payloadFromForm() {
    const fd = new FormData(form);
    return {
      name: fd.get('name') || '',
      whatsapp: fd.get('whatsapp') || '',
      city: fd.get('city') || '',
      state: fd.get('state') || '',
      property_type: fd.get('property_type') || '',
      monthly_bill: fd.get('monthly_bill') || '',
      property_ownership: fd.get('property_ownership') || '',
      roof_type: fd.get('roof_type') || '',
      installation_timeline: fd.get('installation_timeline') || '',
      consent: fd.get('consent') === 'on',
      website: fd.get('website') || '',
      source: 'site_simulador',
      ...(window.qcsUtmPayload ? window.qcsUtmPayload() : {})
    };
  }

  function showResult(result, classification, whatsappUrl) {
    resultBox.classList.add('active');
    resultBox.querySelector('[data-kwp]').textContent = fmtNumber(result.estimated_kwp, 1) + ' kWp';
    resultBox.querySelector('[data-investment]').textContent = `${fmtMoney(result.investment_min)} a ${fmtMoney(result.investment_max)}`;
    resultBox.querySelector('[data-savings]').textContent = `${fmtMoney(result.savings_min)} a ${fmtMoney(result.savings_max)}`;
    resultBox.querySelector('[data-payback]').textContent = `${fmtNumber(result.payback_min, 1)} a ${fmtNumber(result.payback_max, 1)} anos`;
    resultBox.querySelector('[data-classification]').textContent = classification || 'Lead recebido';
    const whatsappButton = resultBox.querySelector('[data-whatsapp-url]');
    if (whatsappButton && whatsappUrl) whatsappButton.href = whatsappUrl;
    resultBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  async function parseJsonResponse(res) {
    const contentType = (res.headers.get('content-type') || '').toLowerCase();

    if (!contentType.includes('application/json')) {
      const text = await res.text().catch(() => '');
      const hint = text && text.trim().startsWith('<') ? 'O PHP retornou HTML/erro do servidor em vez de JSON.' : 'Resposta inválida do servidor.';
      throw new Error(`${hint} Código HTTP: ${res.status}.`);
    }

    const json = await res.json();
    if (!res.ok) {
      throw new Error(json.message || `Erro HTTP ${res.status} ao enviar a simulação.`);
    }
    return json;
  }

  function validate(payload) {
    if (!payload.name || !payload.whatsapp || !payload.city || !payload.state || !payload.property_type || !payload.monthly_bill || !payload.property_ownership || !payload.roof_type || !payload.installation_timeline) {
      return 'Preencha todos os campos obrigatórios.';
    }
    const bill = Number(String(payload.monthly_bill).replace('.', '').replace(',', '.'));
    if (!bill || bill <= 0) return 'Informe o valor médio da conta de luz.';
    if (!payload.consent) return 'Aceite o contato de empresas parceiras para receber orçamentos.';
    return '';
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const payload = payloadFromForm();
    const error = validate(payload);
    if (error) return setMessage('error', error);

    submitBtn.disabled = true;
    submitBtn.textContent = 'Calculando...';
    setMessage('ok', 'Processando sua simulação...');
    try {
      const res = await fetch('/api/leads.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await parseJsonResponse(res);
      if (!json.ok) {
        setMessage('error', json.message || 'Não foi possível enviar sua simulação.');
        return;
      }
      setMessage('ok', json.message || 'Simulação enviada com sucesso.');
      showResult(json.result, json.classification, json.whatsapp_url);
    } catch (e) {
      console.error('[QCS] Falha no envio da simulação:', e);
      setMessage('error', e.message || 'Não foi possível enviar. Verifique a configuração do domínio, PHP e banco de dados.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Simular meu custo agora';
    }
  });

  if (stateSelect) {
    stateSelect.addEventListener('change', () => {
      fetch('/api/settings.php?state=' + encodeURIComponent(stateSelect.value)).catch(() => null);
    });
  }
})();
