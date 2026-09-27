/**
 * charts.js — Gráficos Chart.js para dashboards SATI-Alerta
 */

const SatiCharts = {
  instances: {},

  // Paleta dark para todos os gráficos
  palette: {
    teal:     '#00b4d8',
    green:    '#06d6a0',
    blue:     '#0077b6',
    warning:  '#ffd166',
    danger:   '#ef233c',
    orange:   '#f77f00',
    muted:    'rgba(122,144,170,0.5)',
    gridLine: 'rgba(255,255,255,0.05)',
    text:     '#7a90aa',
  },

  defaults: {
    responsive:          true,
    maintainAspectRatio: false,
    animation:           { duration: 600, easing: 'easeInOutQuart' },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0f2040',
        borderColor:     'rgba(0,180,216,0.2)',
        borderWidth:     1,
        titleColor:      '#e2eaf4',
        bodyColor:       '#7a90aa',
        padding:         10,
        cornerRadius:    8,
      },
    },
    scales: {
      x: {
        grid:  { color: 'rgba(255,255,255,0.04)', drawBorder: false },
        ticks: { color: '#7a90aa', font: { size: 11 } },
      },
      y: {
        grid:  { color: 'rgba(255,255,255,0.04)', drawBorder: false },
        ticks: { color: '#7a90aa', font: { size: 11 } },
        border: { dash: [4,4] },
      },
    },
  },

  /**
   * Cria gráfico de linha para série temporal de sensor
   * @param {string} canvasId
   * @param {Array}  labels  - timestamps formatados
   * @param {Array}  values  - leituras numéricas
   * @param {string} label   - nome da variável
   * @param {string} color   - cor da linha
   * @param {string} unit    - unidade para tooltip
   */
  timeSeriesLine(canvasId, labels, values, label = '', color = null, unit = '') {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    if (this.instances[canvasId]) {
      this.instances[canvasId].destroy();
    }

    const c = color ?? this.palette.teal;

    this.instances[canvasId] = new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label,
          data: values,
          borderColor:     c,
          backgroundColor: `${c}18`,
          pointBackgroundColor: c,
          pointRadius:     3,
          pointHoverRadius:6,
          borderWidth:     2,
          fill:            true,
          tension:         0.4,
        }],
      },
      options: {
        ...this.defaults,
        plugins: {
          ...this.defaults.plugins,
          tooltip: {
            ...this.defaults.plugins.tooltip,
            callbacks: {
              label: (ctx) => ` ${ctx.parsed.y.toFixed(1)} ${unit}`,
            },
          },
        },
      },
    });

    return this.instances[canvasId];
  },

  /**
   * Gráfico de barras — comparativo de sensores por tipo
   */
  barChart(canvasId, labels, datasets) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    if (this.instances[canvasId]) this.instances[canvasId].destroy();

    this.instances[canvasId] = new Chart(canvas, {
      type: 'bar',
      data: { labels, datasets },
      options: {
        ...this.defaults,
        borderRadius: 6,
        borderSkipped: false,
      },
    });
  },

  /**
   * Gauge semicircular usando Doughnut Chart (umidade do solo)
   */
  gauge(canvasId, value, max = 100, color = null) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    if (this.instances[canvasId]) this.instances[canvasId].destroy();

    const pct = Math.min(100, Math.max(0, (value / max) * 100));
    const c   = color ?? this.getValueColor(pct);

    this.instances[canvasId] = new Chart(canvas, {
      type: 'doughnut',
      data: {
        datasets: [{
          data:            [pct, 100 - pct],
          backgroundColor: [c, 'rgba(255,255,255,0.05)'],
          borderColor:     ['transparent', 'transparent'],
          borderWidth:     0,
          circumference:   180,
          rotation:        270,
        }],
      },
      options: {
        responsive:          true,
        maintainAspectRatio: false,
        cutout:              '75%',
        animation:           { duration: 800, easing: 'easeInOutBounce' },
        plugins: {
          legend:  { display: false },
          tooltip: { enabled: false },
        },
      },
    });
  },

  /**
   * Gráfico de risco preditivo — doughnut de nível 0-100
   */
  riskDonut(canvasId, level) {
    const c = this.getValueColor(level, true);
    return this.gauge(canvasId, level, 100, c);
  },

  getValueColor(pct, inverted = false) {
    const adjusted = inverted ? pct : pct;
    if (adjusted >= 80) return this.palette.danger;
    if (adjusted >= 60) return this.palette.orange;
    if (adjusted >= 40) return this.palette.warning;
    if (adjusted >= 20) return this.palette.teal;
    return this.palette.green;
  },

  /**
   * Atualiza um gráfico de série temporal com novos dados via AJAX
   */
  async updateTimeSeries(canvasId, sensorId, hours = 24, unit = '') {
    try {
      const res  = await fetch(`${SATI.BASE_URL}/api/v1/sensors?readings=1&sensor_id=${sensorId}&hours=${hours}`);
      const data = await res.json();
      const readings = data.readings ?? [];

      if (!readings.length) return;

      const labels = readings.map(r => {
        const d = new Date(r.recorded_at);
        return `${d.getHours().toString().padStart(2,'0')}:${d.getMinutes().toString().padStart(2,'0')}`;
      });
      const values = readings.map(r => parseFloat(r.value));

      const chart = this.instances[canvasId];
      if (chart) {
        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        chart.update('none');
      }
    } catch (e) {
      console.warn('[Charts] updateTimeSeries falhou:', e.message);
    }
  },
};

document.addEventListener('DOMContentLoaded', () => {
  // Inicializa gráficos com dados inline (definidos nas views via PHP)
  document.querySelectorAll('[data-chart]').forEach(el => {
    const type     = el.dataset.chart;
    const labels   = JSON.parse(el.dataset.labels  ?? '[]');
    const values   = JSON.parse(el.dataset.values  ?? '[]');
    const label    = el.dataset.label    ?? '';
    const color    = el.dataset.color    ?? null;
    const unit     = el.dataset.unit     ?? '';

    if (type === 'line')  SatiCharts.timeSeriesLine(el.id, labels, values, label, color, unit);
    if (type === 'gauge') SatiCharts.gauge(el.id, parseFloat(values[0] ?? 0));
    if (type === 'risk')  SatiCharts.riskDonut(el.id, parseInt(values[0] ?? 0));
  });
});
