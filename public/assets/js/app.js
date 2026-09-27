/**
 * app.js — JavaScript principal do SATI-Alerta
 * Gerencia: toasts, sidebar mobile, polling de alertas, formulários
 */

const SATI = {
  BASE_URL: document.querySelector('meta[name="base-url"]')?.content ?? '',
  pollInterval: null,

  init() {
    this.initSidebar();
    this.initToasts();
    this.initAlertPolling();
    this.initManualAlertForm();
    this.initAutoRefresh();
  },

  // ─── Sidebar Mobile Toggle ──────────────────────────────────────
  initSidebar() {
    const btn     = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (!btn || !sidebar) return;

    btn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay?.classList.toggle('show');
    });

    overlay?.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  },

  // ─── Sistema de Toasts em tempo real ───────────────────────────
  initToasts() {
    this._toastStack = document.getElementById('toast-stack');
    if (!this._toastStack) {
      this._toastStack = document.createElement('div');
      this._toastStack.id = 'toast-stack';
      this._toastStack.className = 'toast-stack';
      document.body.appendChild(this._toastStack);
    }
  },

  showToast(message, type = 'info', duration = 5000) {
    const icons = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-triangle-fill', warning: 'bi-exclamation-circle-fill', info: 'bi-info-circle-fill' };
    const toast = document.createElement('div');
    toast.className = `sati-toast toast-${type}`;
    toast.innerHTML = `
      <i class="bi ${icons[type] ?? icons.info} text-${type}"></i>
      <span style="font-size:.83rem;flex:1">${message}</span>
      <button onclick="this.parentElement.remove()" style="background:none;border:none;color:#7a90aa;cursor:pointer;font-size:1rem;">&times;</button>
    `;
    this._toastStack.appendChild(toast);
    setTimeout(() => toast.remove(), duration);
  },

  // ─── Polling de Alertas Ativos ─────────────────────────────────
  initAlertPolling() {
    const badge       = document.getElementById('alert-count-badge');
    const alertList   = document.getElementById('alert-list');
    if (!badge && !alertList) return;

    const regionId = document.querySelector('meta[name="region-id"]')?.content ?? '0';

    const fetchAlerts = async () => {
      try {
        const res  = await fetch(`${this.BASE_URL}/api/v1/alerts?active=1&region_id=${regionId}&limit=15`);
        const data = await res.json();

        if (badge) {
          badge.textContent = data.total ?? 0;
          badge.style.display = data.total > 0 ? 'block' : 'none';
        }

        if (alertList && data.alerts) {
          this.renderAlertList(alertList, data.alerts);
        }

        // Som + toast para novos alertas de emergência
        const emergencies = (data.alerts ?? []).filter(a => a.level_code === 'EMERGENCY');
        if (emergencies.length > 0 && this._lastAlertCount !== undefined && emergencies.length > this._lastEmergencyCount) {
          this.showToast(`🚨 ${emergencies[0].title}`, 'danger', 8000);
        }
        this._lastEmergencyCount = emergencies.length;
        this._lastAlertCount     = data.total;

      } catch (e) {
        console.warn('[SATI] Polling de alertas falhou:', e.message);
      }
    };

    fetchAlerts();
    this.pollInterval = setInterval(fetchAlerts, 30000); // a cada 30s
  },

  renderAlertList(container, alerts) {
    if (!alerts.length) {
      container.innerHTML = '<p class="text-muted-sati text-center py-3" style="font-size:.82rem">Nenhum alerta ativo.</p>';
      return;
    }

    container.innerHTML = alerts.map(a => `
      <div class="alert-item fade-in-up">
        <div class="alert-dot ${a.level_code}"></div>
        <div class="alert-body">
          <div class="alert-title">${this.escapeHtml(a.title)}</div>
          <div class="alert-meta">
            <i class="bi bi-geo-alt"></i> ${this.escapeHtml(a.region_name)}
            &nbsp;·&nbsp; ${this.timeAgo(a.triggered_at)}
            ${a.trigger_value ? `&nbsp;·&nbsp; <strong>${a.trigger_value}</strong> ${a.unit ?? ''}` : ''}
          </div>
        </div>
      </div>
    `).join('');
  },

  // ─── Formulário de alerta manual (painel admin) ────────────────
  initManualAlertForm() {
    const form = document.getElementById('form-manual-alert');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('[type=submit]');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

      const payload = {
        region_id:      form.region_id.value,
        alert_level_id: form.alert_level_id.value,
        title:          form.title.value,
        message:        form.message.value,
      };

      try {
        const res  = await fetch(`${this.BASE_URL}/api/v1/alerts`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (res.ok && data.success) {
          this.showToast('Alerta disparado com sucesso!', 'success');
          form.reset();
          bootstrap.Modal.getInstance(document.getElementById('modalManualAlert'))?.hide();
        } else {
          this.showToast(data.error ?? 'Erro ao disparar alerta.', 'danger');
        }
      } catch (err) {
        this.showToast('Erro de conexão.', 'danger');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Disparar Alerta';
      }
    });
  },

  // ─── Auto-refresh de páginas de dados ─────────────────────────
  initAutoRefresh() {
    const meta = document.querySelector('meta[name="auto-refresh"]');
    if (meta) {
      const seconds = parseInt(meta.content, 10);
      setTimeout(() => location.reload(), seconds * 1000);
    }
  },

  // ─── Utilitários ──────────────────────────────────────────────
  escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  },

  timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)    return 'agora mesmo';
    if (diff < 3600)  return `${Math.floor(diff/60)} min atrás`;
    if (diff < 86400) return `${Math.floor(diff/3600)}h atrás`;
    return `${Math.floor(diff/86400)}d atrás`;
  },

  formatNumber(n, decimals = 1) {
    return (parseFloat(n) || 0).toFixed(decimals);
  },
};

document.addEventListener('DOMContentLoaded', () => SATI.init());
