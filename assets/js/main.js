(function () {
  const nav = document.querySelector('.nav-links');
  const toggle = document.querySelector('.mobile-toggle');
  if (toggle && nav) {
    toggle.addEventListener('click', () => nav.classList.toggle('open'));
  }

  const utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
  const params = new URLSearchParams(window.location.search);
  utmKeys.forEach((key) => {
    const value = params.get(key);
    if (value) window.sessionStorage.setItem(key, value);
  });

  window.qcsUtmPayload = function () {
    const payload = {};
    utmKeys.forEach((key) => payload[key] = window.sessionStorage.getItem(key) || '');
    return payload;
  };

  const partnerForm = document.querySelector('[data-partner-form]');
  if (partnerForm) {
    partnerForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      const message = partnerForm.querySelector('[data-form-message]');
      const button = partnerForm.querySelector('button[type="submit"]');
      const fd = new FormData(partnerForm);
      const projectTypes = fd.getAll('project_types[]');
      const serviceStates = (fd.get('service_states') || '').split(',').map(s => s.trim()).filter(Boolean);
      const payload = Object.fromEntries(fd.entries());
      payload.project_types = projectTypes;
      payload.service_states = serviceStates;
      payload.consent = fd.get('consent') === 'on';
      button.disabled = true;
      button.textContent = 'Enviando...';
      try {
        const res = await fetch('/api/partners.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        message.className = 'form-message ' + (json.ok ? 'ok' : 'error');
        message.textContent = json.message || (json.ok ? 'Cadastro enviado.' : 'Erro ao enviar.');
        if (json.ok) partnerForm.reset();
      } catch (e) {
        message.className = 'form-message error';
        message.textContent = 'Não foi possível enviar. Tente novamente.';
      } finally {
        button.disabled = false;
        button.textContent = 'Quero receber leads';
      }
    });
  }
})();
