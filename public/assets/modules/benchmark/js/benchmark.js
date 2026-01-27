/* modules/benchmark/assets/js/benchmark.js */
(function ($) {
    if (!window.BMC_CONFIG || typeof $ === 'undefined') {
        return;
    }

    const config = window.BMC_CONFIG || {};
    const directApi = config.directApi === true;
    const yearKey = 'a\u00f1o';
    const analyzeTimeoutMs = Number.isFinite(Number(config.analyzeTimeoutMs))
        ? Number(config.analyzeTimeoutMs)
        : 300000;
    const api = axios.create({
        baseURL: config.apiBase || '/api/bi/benchmark',
        timeout: 20000,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const state = {
        charts: {},
        tables: {},
        cache: {},
        rows: [],
        analysis: {},
        product: {
            general: [],
            period: [],
            monthly: []
        }
    };

    const storagePrefix = 'bmc_';

    function storageGet(key, fallback) {
        try {
            const raw = localStorage.getItem(storagePrefix + key);
            return raw ? JSON.parse(raw) : fallback;
        } catch (err) {
            return fallback;
        }
    }

    function storageSet(key, value) {
        try {
            localStorage.setItem(storagePrefix + key, JSON.stringify(value));
        } catch (err) {
            return;
        }
    }

    function safeNumber(value) {
        const num = parseFloat(value);
        return Number.isFinite(num) ? num : 0;
    }

    function normalizePercent(value) {
        const num = safeNumber(value);
        if (Math.abs(num) > 1.2) {
            return num / 100;
        }
        return num;
    }

    function parsePeriodoKey(label) {
        if (!label) return 0;
        const text = String(label).trim();
        if (!text) return 0;
        const lower = text.toLowerCase();
        const months = {
            enero: 1,
            febrero: 2,
            marzo: 3,
            abril: 4,
            mayo: 5,
            junio: 6,
            julio: 7,
            agosto: 8,
            septiembre: 9,
            setiembre: 9,
            octubre: 10,
            noviembre: 11,
            diciembre: 12
        };
        let match = lower.match(/(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\s*(\d{4})/);
        if (match) {
            return parseInt(match[2], 10) * 100 + months[match[1]];
        }
        match = lower.match(/(\d{4})[\/-](\d{1,2})/);
        if (match) {
            return parseInt(match[1], 10) * 100 + parseInt(match[2], 10);
        }
        match = lower.match(/(\d{1,2})[\/-](\d{4})/);
        if (match) {
            return parseInt(match[2], 10) * 100 + parseInt(match[1], 10);
        }
        const yearMatch = lower.match(/(20\d{2}|19\d{2})/);
        if (yearMatch) {
            return parseInt(yearMatch[1], 10) * 100;
        }
        return 0;
    }

    function sortByPeriodo(list) {
        if (!Array.isArray(list)) return [];
        const withKeys = list.map((item, index) => {
            const label = item.periodo || item.month || item.label || item.fecha || item.period || '';
            return { item, index, key: parsePeriodoKey(label) };
        });
        withKeys.sort((a, b) => {
            if (a.key && b.key && a.key !== b.key) {
                return a.key - b.key;
            }
            if (a.key && !b.key) return -1;
            if (!a.key && b.key) return 1;
            return a.index - b.index;
        });
        return withKeys.map((entry) => entry.item);
    }

    function pickLatestByPeriodo(list) {
        if (!Array.isArray(list) || !list.length) return null;
        let latest = list[0];
        let latestKey = parsePeriodoKey(latest.periodo || latest.month || latest.label || latest.fecha || latest.period || '');
        list.forEach((item) => {
            const key = parsePeriodoKey(item.periodo || item.month || item.label || item.fecha || item.period || '');
            if (key && (!latestKey || key > latestKey)) {
                latestKey = key;
                latest = item;
            }
        });
        return latest;
    }

    function unwrapPayload(payload) {
        let current = payload;
        let guard = 0;
        while (current && typeof current === 'object' && guard < 4) {
            if (current.success === false) {
                break;
            }
            if (current.data && typeof current.data === 'object') {
                const inner = current.data;
                if (inner.success !== undefined || inner.data !== undefined || inner.message !== undefined) {
                    current = inner;
                    guard += 1;
                    continue;
                }
            }
            break;
        }
        if (current && typeof current === 'object' && current.data !== undefined) {
            if (current.success !== undefined || current.message !== undefined) {
                return current.data;
            }
        }
        return current;
    }

    function objectValuesList(value) {
        if (!value || typeof value !== 'object' || Array.isArray(value)) {
            return [];
        }
        return Object.values(value).filter((entry) => entry && typeof entry === 'object');
    }

    function renderTableRows(tableId, rows, columns, emptyLabel) {
        const $table = $(tableId);
        if (!$table.length) return;

        const $tbody = $table.find('tbody');
        $tbody.empty();

        const safeRows = Array.isArray(rows) ? rows : [];
        if (!safeRows.length) {
            const colspan = columns.length || 1;
            $tbody.append('<tr><td colspan="' + colspan + '" class="text-muted">Sin datos</td></tr>');
            return;
        }

        safeRows.forEach((row) => {
            const $tr = $('<tr/>');
            columns.forEach((col) => {
                const value = typeof col.value === 'function' ? col.value(row) : row[col.key];
                const $td = $('<td/>');
                if (col.html) {
                    $td.html(value ?? '');
                } else {
                    $td.text(value ?? '');
                }
                $tr.append($td);
            });
            $tbody.append($tr);
        });
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(safeNumber(value));
    }

    function formatNumber(value, decimals) {
        const digits = Number.isFinite(decimals) ? decimals : 2;
        return new Intl.NumberFormat('es-CO', {
            minimumFractionDigits: digits,
            maximumFractionDigits: digits
        }).format(safeNumber(value));
    }

    function formatPercent(value, decimals) {
        const digits = Number.isFinite(decimals) ? decimals : 2;
        const raw = safeNumber(value);
        const normalized = Math.abs(raw) > 1 ? raw : raw * 100;
        return normalized.toFixed(digits) + '%';
    }

    function formatRatio(value) {
        const raw = safeNumber(value);
        const normalized = Math.abs(raw) > 1 ? raw : raw * 100;
        return normalized.toFixed(5) + '%';
    }

    function toMillions(value, flagged) {
        const raw = safeNumber(value);
        if (flagged) {
            return raw;
        }
        if (Math.abs(raw) > 1000000) {
            return raw / 1000000;
        }
        return raw;
    }

    function isCorreagro(name) {
        if (!name) return false;
        return String(name).toLowerCase().includes('correagro');
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message || 'Error al cargar datos'
        });
    }

    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'OK',
            text: message || 'Operacion exitosa',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function updateTimestamp() {
        const now = new Date();
        const label = now.toLocaleString('es-CO');
        $('.bmc-last-updated').text('Actualizado: ' + label);
    }

    function setLoading($container, loading) {
        if (!$container || !$container.length) {
            return;
        }
        let $loader = $container.find('.bmc-loader');
        if (!$loader.length) {
            $container.css('position', 'relative');
            $loader = $('<div class="bmc-loader"><div class="spinner-border text-primary"></div></div>');
            $container.append($loader);
        }
        $loader.toggleClass('d-none', !loading);
    }

    function getPage() {
        const $app = $('#benchmark-app');
        return config.page || $app.data('benchmark-page') || '';
    }

    async function apiGet(path, params) {
        try {
            const initialParams = params || {};
            let url = path;
            let queryParams = initialParams;

            if (directApi) {
                if (path === '/summary') {
                    url = '/stats/summary';
                }

                if (path === '/reports') {
                    queryParams = { ...initialParams };
                    if (queryParams.year !== undefined) {
                        queryParams[yearKey] = queryParams.year;
                        delete queryParams.year;
                    }
                }

                if (path === '/report' && initialParams.id) {
                    url = '/reports/' + encodeURIComponent(initialParams.id);
                    queryParams = {};
                }

                if (path === '/trends/scb' && initialParams.scb) {
                    url = '/trends/scb/' + encodeURIComponent(initialParams.scb);
                    queryParams = {};
                }
            }

            const response = await api.get(url, { params: queryParams });
            const payload = response.data;
            if (!payload) {
                throw new Error('Respuesta vacia del API');
            }
            if (typeof payload !== 'object') {
                throw new Error('Respuesta no JSON');
            }
            if (payload.success === false) {
                throw new Error(payload && payload.message ? payload.message : 'Error API');
            }
            return unwrapPayload(payload);
        } catch (err) {
            throw new Error(err.message || 'Error API');
        }
    }

    async function apiPost(path, data, options) {
        try {
            const response = await api.post(path, data, options || {});
            const payload = response.data;
            if (!payload) {
                throw new Error('Respuesta vacia del API');
            }
            if (typeof payload !== 'object') {
                throw new Error('Respuesta no JSON');
            }
            if (payload.success === false) {
                throw new Error(payload && payload.message ? payload.message : 'Error API');
            }
            return unwrapPayload(payload);
        } catch (err) {
            throw new Error(err.message || 'Error API');
        }
    }

    function normalizeSeries(value) {
        if (Array.isArray(value)) {
            return value.map(safeNumber);
        }
        if (typeof value === 'string') {
            return value.split(',').map(safeNumber);
        }
        return [];
    }

    function normalizeReportRow(raw, index) {
        const name = raw.scb || raw.comisionista || raw.nombre || raw.name || raw.razon_social || 'N/D';
        const id = raw.id || raw.scb_id || raw.report_id || raw.codigo || raw.code || name;
        const position = parseInt(raw.position || raw.posicion || raw.rank || raw.ranking || (Number.isFinite(index) ? index + 1 : 0), 10);
        const change = safeNumber(raw.change ?? raw.cambio ?? raw.delta ?? 0);
        const share = safeNumber(
            raw.participacion
                ?? raw.participacion_pct
                ?? raw.participacion_actual_pct
                ?? raw.market_share
                ?? raw.share
                ?? raw.cuota
                ?? 0
        );

        const volumeRaw = raw.monto_millones
            ?? raw['año_actual_millones']
            ?? raw['anio_actual_millones']
            ?? raw.volumen_millones
            ?? raw.volume_millions
            ?? raw.volumen
            ?? raw.volume
            ?? raw.total
            ?? 0;
        const volume = toMillions(
            volumeRaw,
            raw.monto_millones !== undefined
                || raw['año_actual_millones'] !== undefined
                || raw['anio_actual_millones'] !== undefined
                || raw.volumen_millones !== undefined
                || raw.volume_millions !== undefined
        );

        const growth = safeNumber(
            raw.variacion_pct
                ?? raw.variacion_porcentual
                ?? raw.crecimiento
                ?? raw.growth
                ?? raw.growth_pct
                ?? 0
        );
        const commission = safeNumber(raw.comision ?? raw.commission ?? raw.comision_cop ?? 0);
        const negotiated = safeNumber(raw.negociado ?? raw.traded ?? raw.trading_volume ?? raw.volume ?? raw.volumen ?? 0);
        const margin = negotiated > 0 ? commission / negotiated : 0;

        return {
            id,
            name,
            position,
            change,
            share,
            volume,
            growth,
            commission,
            negotiated,
            margin,
            trend3: normalizeSeries(raw.trend3 ?? raw.tendencia_3 ?? raw.trend_3 ?? []),
            trend6: normalizeSeries(raw.trend6 ?? raw.tendencia_6 ?? raw.trend_6 ?? []),
            trend12: normalizeSeries(raw.trend12 ?? raw.tendencia_12 ?? raw.trend_12 ?? [])
        };
    }

    function extractReportRows(payload) {
        const base = unwrapPayload(payload);
        if (!base) return [];
        if (Array.isArray(base)) return base;

        const keys = ['reports', 'reportes', 'data', 'items', 'rows', 'result', 'ranking', 'ranking_scb', 'scb', 'records', 'list'];
        for (const key of keys) {
            if (Array.isArray(base[key])) {
                return base[key];
            }
            const fromObject = objectValuesList(base[key]);
            if (fromObject.length) {
                return fromObject;
            }
        }
        if (base.data && typeof base.data === 'object') {
            for (const key of keys) {
                if (Array.isArray(base.data[key])) {
                    return base.data[key];
                }
                const fromObject = objectValuesList(base.data[key]);
                if (fromObject.length) {
                    return fromObject;
                }
            }
        }
        if (base.ranking_scb && typeof base.ranking_scb === 'object') {
            for (const key of ['items', 'rows', 'data', 'results']) {
                if (Array.isArray(base.ranking_scb[key])) {
                    return base.ranking_scb[key];
                }
                const fromObject = objectValuesList(base.ranking_scb[key]);
                if (fromObject.length) {
                    return fromObject;
                }
            }
            const fromObject = objectValuesList(base.ranking_scb);
            if (fromObject.length) {
                return fromObject;
            }
        }
        return [];
    }

    function renderSemaforo(growth) {
        const value = safeNumber(growth);
        let cls = 'bmc-semaforo-warning';
        if (value >= 0.05) cls = 'bmc-semaforo-success';
        if (value < 0) cls = 'bmc-semaforo-danger';
        return '<span class="bmc-semaforo ' + cls + '"></span>';
    }

    function renderChange(change) {
        const value = safeNumber(change);
        const arrow = value > 0 ? '<i class="fas fa-arrow-up"></i>' : value < 0 ? '<i class="fas fa-arrow-down"></i>' : '<i class="fas fa-minus"></i>';
        const cls = value > 0 ? 'text-success' : value < 0 ? 'text-danger' : 'text-muted';
        return '<span class="' + cls + '">' + arrow + ' ' + formatNumber(Math.abs(value), 2) + '</span>';
    }

    function renderVariation(value) {
        const num = safeNumber(value);
        const arrow = num > 0 ? '<i class="fas fa-arrow-up"></i>' : num < 0 ? '<i class="fas fa-arrow-down"></i>' : '<i class="fas fa-minus"></i>';
        const cls = num > 0 ? 'text-success' : num < 0 ? 'text-danger' : 'text-muted';
        return '<span class="' + cls + '">' + arrow + ' ' + formatPercent(Math.abs(num), 2) + '</span>';
    }

    function renderSparklineCell(row) {
        const spark3 = row.trend3.length ? row.trend3.join(',') : '';
        const spark6 = row.trend6.length ? row.trend6.join(',') : '';
        const spark12 = row.trend12.length ? row.trend12.join(',') : '';

        return (
            '<div class="bmc-sparkline-group">' +
            '<canvas class="bmc-sparkline" data-values="' + spark3 + '" data-color="#0d6efd"></canvas>' +
            '<canvas class="bmc-sparkline" data-values="' + spark6 + '" data-color="#20c997"></canvas>' +
            '<canvas class="bmc-sparkline" data-values="' + spark12 + '" data-color="#ffc107"></canvas>' +
            '</div>'
        );
    }

    function renderSparklines() {
        $('.bmc-sparkline').each(function () {
            const $el = $(this);
            if ($el.data('chart')) {
                return;
            }
            const raw = ($el.data('values') || '').toString();
            const values = raw ? raw.split(',').map(safeNumber) : [0, 0, 0];
            const color = $el.data('color') || '#0d6efd';
            const ctx = $el[0].getContext('2d');

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: values.map((_, idx) => idx + 1),
                    datasets: [{
                        data: values,
                        borderColor: color,
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: { x: { display: false }, y: { display: false } }
                }
            });

            $el.data('chart', chart);
        });
    }

    function updateKpis(rows) {
        if (!rows.length) {
            $('[data-kpi]').text('--');
            return;
        }

        const totals = rows.reduce(
            (acc, row) => {
                acc.volume += row.volume;
                acc.commission += row.commission;
                acc.negotiated += row.negotiated;
                return acc;
            },
            { volume: 0, commission: 0, negotiated: 0 }
        );

        const avgMargin = totals.negotiated > 0 ? totals.commission / totals.negotiated : 0;

        $('[data-kpi="total-volume"]').text(formatNumber(totals.volume, 2));
        $('[data-kpi="total-commission"]').text(formatCurrency(totals.commission));
        $('[data-kpi="avg-margin"]').text(formatRatio(avgMargin));
        $('[data-kpi="total-scbs"]').text(rows.length);
    }

    function updateCorreagroPanel(rows, trends, sectors) {
        const corre = rows.find((row) => isCorreagro(row.name));
        const sorted = rows.slice().sort((a, b) => a.position - b.position);

        if (!corre) {
            $('#bmc-correagro-position').text('--');
            $('#bmc-correagro-share').text('--');
            $('#bmc-gap-1').text('--');
            $('#bmc-gap-2').text('--');
            $('#bmc-correagro-projection').text('--');
            return;
        }

        const leader = sorted[0];
        const second = sorted[1];

        $('#bmc-correagro-position').text(corre.position || '--');
        $('#bmc-correagro-share').text(formatPercent(corre.share, 2));

        if (leader) {
            const gap = Math.max(leader.volume - corre.volume, 0);
            const gapPct = leader.volume > 0 ? gap / leader.volume : 0;
            $('#bmc-gap-1').text(formatNumber(gap, 2) + ' MM (' + formatPercent(gapPct, 2) + ')');
        }

        if (second) {
            const gap = Math.max(second.volume - corre.volume, 0);
            const gapPct = second.volume > 0 ? gap / second.volume : 0;
            $('#bmc-gap-2').text(formatNumber(gap, 2) + ' MM (' + formatPercent(gapPct, 2) + ')');
        }

        const projection = estimateMonthsToReach(corre, leader);
        $('#bmc-correagro-projection').text(projection);

        if (trends && trends.labels && trends.values) {
            renderLineChart('bmc-correagro-trend', trends.labels, [
                { label: 'Correagro', data: trends.values, color: '#00A651' }
            ]);
        }

        if (Array.isArray(sectors) && sectors.length) {
            const list = sectors.slice(0, 5).map((item) => {
                return '<li class="list-group-item d-flex justify-content-between">' +
                    '<span>' + item.sector + '</span>' +
                    '<span class="text-muted">' + formatPercent(item.share || 0, 2) + '</span>' +
                    '</li>';
            });
            $('#bmc-correagro-sectors').html(list.join(''));
        }
    }

    function estimateMonthsToReach(corre, leader) {
        if (!corre || !leader) return '--';
        const gap = leader.volume - corre.volume;
        if (gap <= 0) return 'Ya en el top';

        const trend = Array.isArray(corre.trend6) && corre.trend6.length ? corre.trend6 : corre.trend12;
        if (!trend || trend.length < 2) return 'Sin tendencia';

        const diffs = [];
        for (let i = 1; i < trend.length; i++) {
            diffs.push(trend[i] - trend[i - 1]);
        }
        const avg = diffs.reduce((acc, val) => acc + val, 0) / diffs.length;
        if (avg <= 0) return 'Sin crecimiento';

        const months = Math.ceil(gap / avg);
        return months + ' meses aprox.';
    }

    function renderLineChart(canvasId, labels, datasets) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (state.charts[canvasId]) {
            state.charts[canvasId].destroy();
        }

        const chartData = datasets.map((item) => ({
            label: item.label,
            data: item.data,
            borderColor: item.color,
            backgroundColor: item.color + '33',
            tension: 0.35,
            fill: false
        }));

        state.charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: chartData },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { ticks: { callback: (value) => formatNumber(value, 2) } } }
            }
        });
    }

    function renderProductChart(canvasId, type, labels, datasetLabel, values) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (state.charts[canvasId]) {
            state.charts[canvasId].destroy();
        }

        const palette = ['#16a34a', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#22c55e', '#0f172a'];
        const background = labels.map((_, idx) => palette[idx % palette.length]);
        const metric = $('#bmc-product-chart-metric').val() || 'monto';
        const formatter = metric === 'monto'
            ? (value) => formatNumber(value, 2) + ' MM'
            : (value) => formatPercent(value, 2);

        const dataset = {
            label: datasetLabel,
            data: values,
            backgroundColor: type === 'doughnut' ? background : 'rgba(22, 163, 74, 0.6)',
            borderColor: type === 'line' ? '#16a34a' : background,
            borderWidth: 2,
            tension: 0.35,
            fill: type !== 'line'
        };

        state.charts[canvasId] = new Chart(ctx, {
            type: type === 'doughnut' ? 'doughnut' : type,
            data: { labels, datasets: [dataset] },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = context.parsed;
                                return datasetLabel + ': ' + formatter(value);
                            }
                        }
                    }
                },
                scales: type === 'doughnut'
                    ? {}
                    : {
                        y: {
                            ticks: {
                                callback: function (value) {
                                    return formatter(value);
                                }
                            }
                        }
                    }
            }
        });
    }

    function renderBarChart(canvasId, labels, datasets, stacked) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (state.charts[canvasId]) {
            state.charts[canvasId].destroy();
        }

        state.charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { stacked: !!stacked },
                    y: { stacked: !!stacked, ticks: { callback: (value) => formatNumber(value, 2) } }
                }
            }
        });
    }

    function renderDoughnutChart(canvasId, labels, values, labelText) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (state.charts[canvasId]) {
            state.charts[canvasId].destroy();
        }

        const palette = ['#16a34a', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'];
        const background = labels.map((_, idx) => palette[idx % palette.length]);

        state.charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    label: labelText || 'Participacion',
                    data: values,
                    backgroundColor: background,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                return label + ': ' + formatPercent(value, 2);
                            }
                        }
                    }
                }
            }
        });
    }

    function renderScatterChart(canvasId, datasets) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (state.charts[canvasId]) {
            state.charts[canvasId].destroy();
        }

        state.charts[canvasId] = new Chart(ctx, {
            type: 'scatter',
            data: { datasets },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: {
                        title: { display: true, text: 'Market Share' },
                        ticks: { callback: (value) => formatPercent(value, 2) }
                    },
                    y: {
                        title: { display: true, text: 'Crecimiento' },
                        ticks: { callback: (value) => formatPercent(value, 2) }
                    }
                }
            }
        });
    }

    async function loadDashboard() {
        const year = $('#bmc-year').val() || config.defaultYear;
        const limit = $('#bmc-limit').val() || 50;
        const period = $('#bmc-period').val() || 6;

        storageSet('dashboard_filters', { year, limit, period });

        const $tableCard = $('#bmc-ranking-table').closest('.card');
        setLoading($tableCard, true);

        try {
            const reportData = await apiGet('/reports', { limit: 200, year });
            const reports = extractReportRows(reportData);
            const reportId = pickLatestReportId(reports, year, period);
            if (!reportId) {
                throw new Error('No hay reportes disponibles para el ano seleccionado');
            }

            const reportDetail = await apiGet('/report', { id: reportId });
            const reportPayload = (reportDetail && (reportDetail.reporte || reportDetail.report)) ? (reportDetail.reporte || reportDetail.report) : reportDetail;
            const rows = extractReportRows(reportPayload)
                .map((row, idx) => normalizeReportRow(row, idx))
                .slice(0, parseInt(limit, 10) || undefined);
            state.rows = rows;

            updateKpis(rows);
            renderRankingTable(rows);

            const [trendData, sectorData] = await Promise.all([
                apiGet('/trends/scb', { scb: config.correagroName || 'Correagro S.A.' }).catch(() => null),
                apiGet('/trends/sectores').catch(() => [])
            ]);

            const normalizedTrend = normalizeTrendData(trendData);
            const normalizedSectors = normalizeSectorList(sectorData);

            updateCorreagroPanel(rows, normalizedTrend, normalizedSectors);
            updateTimestamp();
        } catch (err) {
            showError(err.message);
        } finally {
            setLoading($tableCard, false);
        }
    }

    function pickLatestReportId(reports, year, period) {
        if (!Array.isArray(reports) || !reports.length) {
            return null;
        }
        const yearValue = String(year);
        const filtered = reports.filter((item) => {
            const reportYear = item['año'] ?? item.anio ?? item.ano ?? item.year ?? item.fecha_year;
            return reportYear ? String(reportYear) === yearValue : true;
        });
        const candidates = filtered.length ? filtered : reports;
        const sorted = candidates.slice().sort((a, b) => {
            const dateA = Date.parse(a.fecha_carga || '');
            const dateB = Date.parse(b.fecha_carga || '');
            if (!Number.isNaN(dateA) && !Number.isNaN(dateB) && dateA !== dateB) {
                return dateA - dateB;
            }
            const periodLabelA = a.periodo || a.period || a.label || (a.mes && (a['año'] ?? a.anio ?? a.ano ?? a.year) ? a.mes + ' ' + (a['año'] ?? a.anio ?? a.ano ?? a.year) : '');
            const periodLabelB = b.periodo || b.period || b.label || (b.mes && (b['año'] ?? b.anio ?? b.ano ?? b.year) ? b.mes + ' ' + (b['año'] ?? b.anio ?? b.ano ?? b.year) : '');
            const keyA = parsePeriodoKey(periodLabelA);
            const keyB = parsePeriodoKey(periodLabelB);
            if (keyA && keyB && keyA !== keyB) {
                return keyA - keyB;
            }
            return 0;
        });
        const latest = sorted[sorted.length - 1];
        return latest ? latest.id || latest.report_id || latest.codigo || latest.code || null : null;
    }

    function renderRankingTable(rows) {
        const $table = $('#bmc-ranking-table');
        if (!$table.length) return;

        if ($.fn.dataTable.isDataTable($table)) {
            $table.DataTable().clear().destroy();
        }

        const data = rows.map((row) => {
            const highlight = isCorreagro(row.name) ? 'bmc-highlight' : '';
            return [
                row.position || '-',
                '<span class="' + highlight + '">' + row.name + '</span>',
                renderChange(row.change),
                formatPercent(row.share, 2),
                formatNumber(row.volume, 2) + ' MM',
                renderVariation(row.growth),
                renderSparklineCell(row),
                renderSemaforo(row.growth),
                formatCurrency(row.commission),
                formatRatio(row.margin)
            ];
        });

        $table.DataTable({
            data,
            pageLength: 15,
            order: [[0, 'asc']],
            createdRow: function (row, rowData) {
                if (String(rowData[1]).toLowerCase().includes('correagro')) {
                    $(row).addClass('bmc-row-highlight');
                }
            },
            columns: [
                { title: 'Pos' },
                { title: 'SCB' },
                { title: 'Cambio' },
                { title: 'Participacion' },
                { title: 'Volumen (MM)' },
                { title: 'Crecimiento' },
                { title: 'Tendencia', orderable: false },
                { title: 'Semaforo', orderable: false },
                { title: 'Comision' },
                { title: 'Margen' }
            ],
            drawCallback: function () {
                renderSparklines();
            }
        });
    }

    function normalizeTrendData(payload) {
        const base = unwrapPayload(payload);
        if (!base) return null;
        if (base.labels && base.values) {
            return base;
        }
        if (base.data && base.data.labels && base.data.values) {
            return base.data;
        }
        if (Array.isArray(base)) {
            const labels = base.map((item) => item.periodo || item.month || item.label);
            const values = base.map((item) => safeNumber(item.valor || item.value || item.volume || item.volumen || item.monto_millones));
            return { labels, values };
        }
        const history = base.datos_historicos || (base.data && base.data.datos_historicos);
        if (Array.isArray(history) && history.length) {
            const sorted = sortByPeriodo(history);
            const labels = sorted.map((item) => item.periodo || item.month || item.label || item.fecha || item.period || '');
            const values = sorted.map((item) => safeNumber(item.monto_millones ?? item.monto ?? item.valor ?? item.value ?? item.volume ?? item.volumen ?? item.total ?? 0));
            return { labels, values };
        }
        return null;
    }

    function normalizeSectorList(payload) {
        const base = unwrapPayload(payload);
        const rows = extractReportRows(base);
        if (rows.length) {
            return rows.map((row) => ({
                sector: row.sector || row.name || row.nombre || 'N/D',
                share: normalizePercent(row.participacion ?? row.participacion_pct ?? row.share ?? row.market_share ?? 0)
            }));
        }
        if (base && base.sectors && Array.isArray(base.sectors)) {
            return base.sectors.map((row) => ({
                sector: row.sector || row.name || row.nombre || 'N/D',
                share: normalizePercent(row.participacion ?? row.participacion_pct ?? row.share ?? row.market_share ?? 0)
            }));
        }
        if (base && base.sectores && typeof base.sectores === 'object') {
            return Object.keys(base.sectores).map((sector) => {
                const entries = Array.isArray(base.sectores[sector]) ? base.sectores[sector] : [];
                const latest = pickLatestByPeriodo(entries) || entries[entries.length - 1];
                return {
                    sector,
                    share: normalizePercent(latest ? (latest.participacion_pct ?? latest.participacion ?? latest.share ?? latest.market_share ?? 0) : 0)
                };
            });
        }
        return [];
    }

    async function loadComparativa() {
        const selected = $('#bmc-compare-scbs').val() || [];
        const year = $('#bmc-compare-year').val() || config.defaultYear;
        const period = $('#bmc-compare-period').val() || 12;
        const windowSize = $('#bmc-compare-window').val() || 6;

        storageSet('comparativa_filters', { selected, year, period, windowSize });

        if (!selected.length) {
            $('#bmc-compare-notes').html('<li class="list-group-item">Seleccione SCB para comparar.</li>');
            return;
        }

        try {
            const compareData = await apiGet('/compare', { ids: selected.join(',') });
            const normalized = normalizeComparePayload(compareData);

            const sliced = sliceCompareData(normalized, period);
            renderCompareCharts(sliced);
            renderGapAnalysis(sliced, windowSize);
            updateTimestamp();
        } catch (err) {
            showError(err.message);
        }
    }

    function normalizeComparePayload(payload) {
        const base = payload && payload.data ? payload.data : payload;
        if (!base) return { labels: [], series: [] };

        if (Array.isArray(base.series) && Array.isArray(base.labels)) {
            return {
                labels: base.labels,
                series: base.series.map((item) => normalizeCompareSeries(item))
            };
        }

        if (Array.isArray(base.scbs) && Array.isArray(base.months)) {
            return {
                labels: base.months,
                series: base.scbs.map((item) => normalizeCompareSeries(item))
            };
        }

        if (Array.isArray(base)) {
            const grouped = {};
            const labels = [];
            base.forEach((item) => {
                const name = item.scb || item.comisionista || item.name || 'N/D';
                const label = item.month || item.period || item.label || item.fecha || 'Periodo';
                if (!labels.includes(label)) labels.push(label);
                if (!grouped[name]) grouped[name] = [];
                grouped[name].push({ label, item });
            });

            const series = Object.keys(grouped).map((name) => {
                const entries = grouped[name].sort((a, b) => labels.indexOf(a.label) - labels.indexOf(b.label));
                return normalizeCompareSeries({
                    name,
                    values: entries.map((entry) => entry.item)
                }, labels);
            });

            return { labels, series };
        }

        return { labels: [], series: [] };
    }

    function sliceCompareData(normalized, period) {
        const size = parseInt(period, 10);
        if (!size || !normalized.labels || normalized.labels.length <= size) {
            return normalized;
        }

        const start = normalized.labels.length - size;
        return {
            labels: normalized.labels.slice(start),
            series: normalized.series.map((item) => ({
                name: item.name,
                labels: item.labels.slice(start),
                volumes: item.volumes.slice(start),
                shares: item.shares.slice(start),
                growth: item.growth.slice(start)
            }))
        };
    }

    function normalizeCompareSeries(item, labels) {
        const name = item.name || item.scb || item.comisionista || item.nombre || 'N/D';
        const values = item.values || item.data || item.series || [];

        const seriesLabels = labels || (item.labels || item.months || values.map((_, idx) => idx + 1));

        const volumes = values.map((row) => safeNumber(row.monto_millones ?? row.volume ?? row.volumen ?? row.negociado ?? row.value ?? row.total ?? 0));
        const shares = values.map((row) => safeNumber(row.share ?? row.participacion ?? row.participacion_pct ?? row.market_share ?? 0));
        const growth = values.map((row) => safeNumber(row.growth ?? row.crecimiento ?? row.variacion_pct ?? 0));

        return { name, labels: seriesLabels, volumes, shares, growth };
    }

    function renderCompareCharts(normalized) {
        const labels = normalized.labels || [];
        const series = normalized.series || [];

        const palette = ['#0d6efd', '#00A651', '#fd7e14', '#6f42c1', '#20c997', '#dc3545'];

        const shareDatasets = series.map((item, idx) => {
            const color = isCorreagro(item.name) ? '#00A651' : palette[idx % palette.length];
            return ({
                label: item.name,
                data: item.shares.map((val) => (Math.abs(val) > 1 ? val : val * 100)),
                borderColor: color,
                backgroundColor: 'transparent',
                tension: 0.3
            });
        });

        renderLineChart('bmc-compare-share', labels, shareDatasets.map((dataset) => ({
            label: dataset.label,
            data: dataset.data,
            color: dataset.borderColor
        })));

        const volumeDatasets = series.map((item, idx) => {
            const color = isCorreagro(item.name) ? '#00A651' : palette[idx % palette.length];
            return ({
                label: item.name,
                data: item.volumes,
                backgroundColor: color + '99'
            });
        });

        renderBarChart('bmc-compare-volume', labels, volumeDatasets, false);

        const growthDatasets = series.map((item, idx) => {
            const color = isCorreagro(item.name) ? '#00A651' : palette[idx % palette.length];
            return ({
                label: item.name,
                data: item.growth.map((val) => (Math.abs(val) > 1 ? val : val * 100)),
                backgroundColor: color + '99'
            });
        });

        renderBarChart('bmc-compare-growth', labels, growthDatasets, true);
    }

    function renderGapAnalysis(normalized, windowSize) {
        const series = normalized.series || [];
        if (!series.length) return;

        const corre = series.find((item) => isCorreagro(item.name));
        const target = series.find((item) => !isCorreagro(item.name));
        if (!corre || !target) return;

        const correLast = corre.volumes[corre.volumes.length - 1] || 0;
        const targetLast = target.volumes[target.volumes.length - 1] || 0;
        const gap = Math.max(targetLast - correLast, 0);

        $('#bmc-gap-competitor').text(target.name);
        $('#bmc-gap-amount').text(formatNumber(gap, 2) + ' MM');

        const months = estimateMonthsFromSeries(corre.volumes, targetLast, parseInt(windowSize, 10) || 6);
        $('#bmc-gap-target').text(target.name);
        $('#bmc-gap-months').text(months === null ? 'N/A' : months + ' meses');

        const notes = [
            'Correagro ultimo volumen: ' + formatNumber(correLast, 2) + ' MM',
            'Objetivo: ' + formatNumber(targetLast, 2) + ' MM',
            'Ventana crecimiento: ' + windowSize + ' meses'
        ];
        $('#bmc-compare-notes').html(notes.map((note) => '<li class="list-group-item">' + note + '</li>').join(''));
    }

    function estimateMonthsFromSeries(series, targetValue, windowSize) {
        if (!series || series.length < 2) return null;

        const slice = series.slice(-windowSize);
        if (slice.length < 2) return null;

        const diffs = [];
        for (let i = 1; i < slice.length; i++) {
            diffs.push(slice[i] - slice[i - 1]);
        }
        const avg = diffs.reduce((acc, val) => acc + val, 0) / diffs.length;
        if (avg <= 0) return null;

        const current = slice[slice.length - 1];
        const gap = targetValue - current;
        if (gap <= 0) return 0;

        return Math.ceil(gap / avg);
    }

    async function loadSectores() {
        const year = $('#bmc-sector-year').val() || config.defaultYear;
        storageSet('sectores_filters', { year, filter: $('#bmc-sector-filter').val() });

        try {
            const data = await apiGet('/trends/sectores', { year });
            const sectors = normalizeSectorPayload(data);
            renderSectorTable(sectors);
            renderSectorCharts(sectors);
            updateTimestamp();
        } catch (err) {
            showError(err.message);
        }
    }

    function normalizeSectorPayload(payload) {
        const base = unwrapPayload(payload);
        const rows = extractReportRows(base);
        if (base && base.sectores && typeof base.sectores === 'object') {
            const sectors = [];
            Object.keys(base.sectores).forEach((sector) => {
                const entries = Array.isArray(base.sectores[sector]) ? base.sectores[sector] : [];
                const latest = pickLatestByPeriodo(entries) || entries[entries.length - 1];
                if (!latest) return;
                const share = normalizePercent(latest.participacion_pct ?? latest.participacion ?? latest.share ?? latest.market_share ?? 0);
                const growth = normalizePercent(latest.variacion_pct ?? latest.crecimiento ?? latest.growth ?? 0);
                const volume = safeNumber(latest.monto_millones ?? latest.volume ?? latest.volumen ?? 0);
                const corre = { scb: 'Correagro S.A.', share, growth, volume };
                const top = { scb: 'Correagro S.A.', share };
                sectors.push({
                    sector,
                    corre,
                    top,
                    status: classifySector(corre, top)
                });
            });
            return sectors;
        }

        const list = rows.length ? rows : ((base && (base.sectors || base.data)) || []);
        const grouped = {};

        list.forEach((item) => {
            const sector = item.sector || item.name || item.nombre || 'N/D';
            const scb = item.scb || item.comisionista || item.scb_name || item.nombre_scb || 'N/D';
            if (!grouped[sector]) grouped[sector] = [];
            grouped[sector].push({
                scb,
                share: normalizePercent(item.participacion ?? item.participacion_pct ?? item.share ?? item.market_share ?? 0),
                growth: normalizePercent(item.crecimiento ?? item.variacion_pct ?? item.growth ?? 0),
                volume: safeNumber(item.volume ?? item.volumen ?? 0)
            });
        });

        return Object.keys(grouped).map((sector) => {
            const entries = grouped[sector];
            const sorted = entries.slice().sort((a, b) => b.share - a.share);
            const corre = entries.find((item) => isCorreagro(item.scb)) || { scb: 'Correagro S.A.', share: 0, growth: 0, volume: 0 };
            const top = sorted[0] || corre;
            const status = classifySector(corre, top);
            return {
                sector,
                corre,
                top,
                status
            };
        });
    }

    function classifySector(corre, top) {
        if (corre.scb === top.scb) {
            return 'lider';
        }
        if (corre.growth >= 0.05 && corre.share >= 0.1) {
            return 'oportunidad';
        }
        return 'rezago';
    }

    function renderSectorTable(sectors) {
        const filter = $('#bmc-sector-filter').val();
        const filtered = filter === 'all' ? sectors : sectors.filter((item) => item.status === filter);

        const $table = $('#bmc-sectores-table');
        if ($.fn.dataTable.isDataTable($table)) {
            $table.DataTable().clear().destroy();
        }

        const data = filtered.map((item) => {
            const label = item.status === 'lider' ? 'Lider' : item.status === 'oportunidad' ? 'Oportunidad' : 'Rezago';
            const rec = item.status === 'lider' ? 'Defender share' : item.status === 'oportunidad' ? 'Invertir para crecer' : 'Revisar estrategia';
            return [
                item.sector,
                formatPercent(item.corre.share, 2),
                item.top.scb + ' (' + formatPercent(item.top.share, 2) + ')',
                '<span class="badge bmc-status bmc-' + item.status + '">' + label + '</span>',
                rec
            ];
        });

        $table.DataTable({
            data,
            pageLength: 10,
            order: [[0, 'asc']]
        });

        $('#bmc-sector-leader').text(sectors.filter((item) => item.status === 'lider').length);
        $('#bmc-sector-opportunity').text(sectors.filter((item) => item.status === 'oportunidad').length);
        $('#bmc-sector-lag').text(sectors.filter((item) => item.status === 'rezago').length);

        const recs = sectors.slice(0, 5).map((item) => {
            return '<li class="list-group-item">' + item.sector + ': ' + item.status + '</li>';
        });
        $('#bmc-sector-recommendations').html(recs.join('') || '<li class="list-group-item">Sin datos</li>');
    }

    function renderSectorCharts(sectors) {
        const datasets = [
            {
                label: 'Correagro S.A.',
                data: sectors.map((item) => ({
                    x: item.corre.share,
                    y: item.corre.growth,
                    r: Math.max(4, Math.min(12, item.corre.volume / 1000000))
                })),
                backgroundColor: '#00A651'
            }
        ];

        renderScatterChart('bmc-bcg-chart', datasets);
    }

    async function loadTemporal() {
        const year = $('#bmc-temporal-year').val() || config.defaultYear;
        storageSet('temporal_filters', { year });

        try {
            const reportData = await apiGet('/reports', { limit: 10, year });
            const rows = extractReportRows(reportData).map(normalizeReportRow);
            const ids = rows.map((row) => row.id).filter(Boolean);

            const compareData = await apiGet('/compare', { ids: ids.join(',') });
            const normalized = normalizeComparePayload(compareData);

            renderHeatmap(normalized, rows);
            renderVolatilityTable(normalized);
            renderTemporalSelect(normalized);
            renderSeasonality(normalized);
            renderForecast(normalized);
            updateTimestamp();
        } catch (err) {
            showError(err.message);
        }
    }

    function renderHeatmap(normalized, rows) {
        const labels = normalized.labels || [];
        const series = normalized.series || [];
        const container = $('#bmc-heatmap');
        if (!container.length) return;

        const rankByMonth = {};
        labels.forEach((label, idx) => {
            const monthValues = series.map((item) => ({ name: item.name, value: item.volumes[idx] || 0 }));
            monthValues.sort((a, b) => b.value - a.value);
            monthValues.forEach((item, rank) => {
                if (!rankByMonth[item.name]) rankByMonth[item.name] = [];
                rankByMonth[item.name][idx] = rank + 1;
            });
        });

        const header = ['SCB'].concat(labels).map((text) => '<div class="bmc-heatmap-cell bmc-heatmap-head">' + text + '</div>').join('');
        const body = rows.map((row) => {
            const ranks = rankByMonth[row.name] || [];
            const cells = [
                '<div class="bmc-heatmap-cell bmc-heatmap-name ' + (isCorreagro(row.name) ? 'bmc-highlight' : '') + '">' + row.name + '</div>'
            ];
            labels.forEach((_, idx) => {
                const rank = ranks[idx] || '-';
                const cls = rank <= 3 ? 'bmc-heatmap-top' : rank <= 6 ? 'bmc-heatmap-mid' : 'bmc-heatmap-low';
                cells.push('<div class="bmc-heatmap-cell ' + cls + '">' + rank + '</div>');
            });
            return cells.join('');
        }).join('');

        container.html(header + body);
        container.css('grid-template-columns', '160px repeat(' + labels.length + ', minmax(70px, 1fr))');
    }

    function renderVolatilityTable(normalized) {
        const series = normalized.series || [];
        const rows = series.map((item) => {
            const ranks = item.volumes.map((val) => val);
            const avg = ranks.reduce((acc, val) => acc + val, 0) / ranks.length;
            const variance = ranks.reduce((acc, val) => acc + Math.pow(val - avg, 2), 0) / ranks.length;
            const std = Math.sqrt(variance);
            return {
                name: item.name,
                volatility: std,
                trend: ranks[ranks.length - 1] - ranks[0]
            };
        });

        const $table = $('#bmc-volatility-table');
        if ($.fn.dataTable.isDataTable($table)) {
            $table.DataTable().clear().destroy();
        }

        const data = rows.map((row) => [
            row.name,
            formatNumber(row.volatility, 2),
            renderChange(row.trend)
        ]);

        $table.DataTable({
            data,
            pageLength: 5,
            order: [[1, 'desc']]
        });
    }

    function renderTemporalSelect(normalized) {
        const series = normalized.series || [];
        const $select = $('#bmc-temporal-scb');
        if (!$select.length) return;

        $select.empty();
        series.forEach((item) => {
            const selected = isCorreagro(item.name) ? 'selected' : '';
            $select.append('<option value="' + item.name + '" ' + selected + '>' + item.name + '</option>');
        });

        $select.off('change').on('change', function () {
            renderSeasonality(normalized);
            renderForecast(normalized);
        });
    }

    function renderSeasonality(normalized) {
        const series = normalized.series || [];
        const selected = $('#bmc-temporal-scb').val();
        const item = series.find((entry) => entry.name === selected) || series[0];
        if (!item) return;

        renderLineChart('bmc-seasonality', normalized.labels, [
            { label: item.name, data: item.volumes, color: isCorreagro(item.name) ? '#00A651' : '#0d6efd' }
        ]);
    }

    function renderForecast(normalized) {
        const series = normalized.series || [];
        const selected = $('#bmc-temporal-scb').val();
        const item = series.find((entry) => entry.name === selected) || series[0];
        if (!item) return;

        const values = item.volumes;
        const forecast = linearForecast(values, 3);
        const labels = normalized.labels.concat(['+1', '+2', '+3']);
        const data = values.concat(forecast);

        renderLineChart('bmc-forecast', labels, [
            { label: item.name + ' (forecast)', data, color: '#6c757d' }
        ]);
    }

    function linearForecast(values, periods) {
        const n = values.length;
        if (n === 0) return [];

        let sumX = 0;
        let sumY = 0;
        let sumXY = 0;
        let sumX2 = 0;

        values.forEach((val, idx) => {
            const x = idx + 1;
            sumX += x;
            sumY += val;
            sumXY += x * val;
            sumX2 += x * x;
        });

        const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX || 1);
        const intercept = (sumY - slope * sumX) / n;

        const forecast = [];
        for (let i = 1; i <= periods; i++) {
            const x = n + i;
            forecast.push(slope * x + intercept);
        }

        return forecast;
    }

    async function loadReportes() {
        const year = $('#bmc-report-year').val() || config.defaultYear;
        storageSet('reportes_filters', { year });

        try {
            const summary = await apiGet('/summary', { year });
            const list = buildSummaryList(summary);
            $('#bmc-exec-summary').html(list.join(''));
            updateTimestamp();
        } catch (err) {
            showError(err.message);
        }
    }

    async function loadAnalysis() {
        const $year = $('#bmc-analysis-year');
        if (!$year.length) return;

        const year = $year.val() || config.defaultYear;
        const $card = $year.closest('.card');
        setLoading($card, true);

        try {
            const periods = await apiGet('/analysis/periods', { year });
            updateAnalysisReportOptions(periods);
            const reportId = $('#bmc-analysis-report').val();
            await loadAnalysisData(reportId, year);
        } catch (err) {
            showError(err.message);
        } finally {
            setLoading($card, false);
        }
    }

    function updateAnalysisReportOptions(periods) {
        const $select = $('#bmc-analysis-report');
        if (!$select.length) return;

        const saved = storageGet('analysis_filters', null);
        const savedReportId = saved ? saved.reportId : '';

        $select.empty();
        if (!Array.isArray(periods) || !periods.length) {
            $select.append('<option value="">Sin reportes</option>');
            return;
        }

        periods.forEach((row) => {
            const label = row.periodo || row.period || row.label || 'Periodo';
            $select.append('<option value="' + row.id + '">' + label + '</option>');
        });

        if (savedReportId) {
            $select.val(savedReportId);
        } else {
            $select.val(periods[periods.length - 1].id);
        }
    }

    async function loadAnalysisData(reportId, year) {
        if (!reportId) {
            renderAnalysisTables(null);
            renderAnalysisCharts(null);
            return;
        }

        const data = await apiGet('/analysis', { report_id: reportId, year });
        state.analysis = data || {};

        updateAnalysisScbOptions(state.analysis.tables && state.analysis.tables.ranking_scb_acumulado ? state.analysis.tables.ranking_scb_acumulado : []);
        renderAnalysisCharts(state.analysis);
        renderAnalysisTables(state.analysis);
        updateTimestamp();

        const selected = $('#bmc-analysis-scbs').val() || [];
        storageSet('analysis_filters', { year, reportId, scbs: selected });
    }

    function updateAnalysisScbOptions(rows) {
        const $select = $('#bmc-analysis-scbs');
        if (!$select.length) return;

        const saved = storageGet('analysis_filters', null);
        const savedScbs = saved && Array.isArray(saved.scbs) ? saved.scbs : [];

        const names = (rows || [])
            .map((row) => row.name || row.scb || row.comisionista || row.nombre)
            .filter((name) => !!name);

        const unique = Array.from(new Set(names));
        $select.empty();
        unique.forEach((name) => {
            $select.append($('<option/>').val(name).text(name));
        });

        if ($.fn.select2 && !$select.data('select2')) {
            $select.select2({
                placeholder: 'Seleccione SCB clave',
                width: '100%'
            });
        }

        let selected = savedScbs.filter((name) => unique.includes(name));
        if (!selected.length) {
            selected = unique.slice(0, 3);
        }
        $select.val(selected).trigger('change');
    }

    function buildChartFromRows(rows, top) {
        const list = Array.isArray(rows) ? rows.slice() : [];
        list.sort((a, b) => safeNumber(b.actual) - safeNumber(a.actual));
        const slice = list.slice(0, top || 5);
        return {
            labels: slice.map((row) => row.name || 'N/D'),
            values: slice.map((row) => safeNumber(row.actual))
        };
    }

    function renderAnalysisCharts(data) {
        if (!data) {
            renderBarChart('bmc-chart-scb-acum', ['Sin datos'], [{
                label: 'Sin datos',
                data: [0],
                backgroundColor: 'rgba(148, 163, 184, 0.4)'
            }]);
            return;
        }

        const tables = data.tables || {};
        const charts = data.charts || {};

        const topScbAcum = charts.top_scb_acumulado || buildChartFromRows(tables.ranking_scb_acumulado, 5);
        const topScbMes = charts.top_scb_mes || buildChartFromRows(tables.ranking_scb_mes, 5);
        const topSectorAcum = charts.top_sector_acumulado || buildChartFromRows(tables.ranking_sector_total_acumulado, 5);
        const topSectorMes = charts.top_sector_mes || buildChartFromRows(tables.ranking_sector_total_mes, 5);

        renderBarChart('bmc-chart-scb-acum', topScbAcum.labels, [{
            label: 'Volumen (MM)',
            data: topScbAcum.values,
            backgroundColor: 'rgba(22, 163, 74, 0.6)',
            borderColor: '#0f8a3c',
            borderWidth: 1
        }]);

        renderBarChart('bmc-chart-scb-mes', topScbMes.labels, [{
            label: 'Volumen (MM)',
            data: topScbMes.values,
            backgroundColor: 'rgba(14, 165, 233, 0.6)',
            borderColor: '#0284c7',
            borderWidth: 1
        }]);

        renderBarChart('bmc-chart-sector-acum', topSectorAcum.labels, [{
            label: 'Volumen (MM)',
            data: topSectorAcum.values,
            backgroundColor: 'rgba(245, 158, 11, 0.6)',
            borderColor: '#d97706',
            borderWidth: 1
        }]);

        if (document.getElementById('bmc-chart-sector-mes')) {
            renderBarChart('bmc-chart-sector-mes', topSectorMes.labels, [{
                label: 'Volumen (MM)',
                data: topSectorMes.values,
                backgroundColor: 'rgba(99, 102, 241, 0.6)',
                borderColor: '#4f46e5',
                borderWidth: 1
            }]);
        }

        renderAnalysisCompetition(data);
    }

    function renderAnalysisCompetition(data) {
        if (!data) return;

        const tables = data.tables || {};
        const ranking = Array.isArray(tables.ranking_scb_acumulado) ? tables.ranking_scb_acumulado : [];
        const top = ranking.slice().sort((a, b) => safeNumber(b.actual) - safeNumber(a.actual)).slice(0, 5);
        const shareValues = top.map((row) => safeNumber(row.share));
        const shareLabels = top.map((row) => row.name || 'N/D');

        if (document.getElementById('bmc-chart-scb-share')) {
            renderDoughnutChart('bmc-chart-scb-share', shareLabels, shareValues, 'Participacion');
        }

        const selected = ($('#bmc-analysis-scbs').val() || []).filter((name) => !!name);
        const selectedLower = selected.map((name) => String(name).toLowerCase());
        let compareRows = ranking.filter((row) => selectedLower.includes(String(row.name || '').toLowerCase()));
        if (!compareRows.length) {
            compareRows = ranking.slice(0, 3);
        }

        if (document.getElementById('bmc-chart-scb-compare')) {
            renderBarChart('bmc-chart-scb-compare', compareRows.map((row) => row.name || 'N/D'), [
                {
                    label: 'Actual (MM)',
                    data: compareRows.map((row) => safeNumber(row.actual)),
                    backgroundColor: 'rgba(22, 163, 74, 0.65)'
                },
                {
                    label: 'Anterior (MM)',
                    data: compareRows.map((row) => safeNumber(row.previous)),
                    backgroundColor: 'rgba(148, 163, 184, 0.7)'
                }
            ]);
        }
    }

    function renderAnalysisTables(data) {
        const tables = data && data.tables ? data.tables : {};
        const growth = data && data.growth ? data.growth : {};
        const products = data && data.products ? data.products : [];

        renderTableRows('#bmc-growth-scb-acum', growth.scb_acumulado || [], [
            { value: (row) => row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatNumber(row.previous, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true },
            { value: (row) => formatNumber(row.var_abs, 2) + ' MM' }
        ]);

        renderTableRows('#bmc-growth-sector-acum', growth.sector_acumulado || [], [
            { value: (row) => row.name || row.sector || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatNumber(row.previous, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true },
            { value: (row) => formatNumber(row.var_abs, 2) + ' MM' }
        ]);

        renderTableRows('#bmc-growth-scb-mes', growth.scb_mes || [], [
            { value: (row) => row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatNumber(row.previous, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true },
            { value: (row) => formatNumber(row.var_abs, 2) + ' MM' }
        ]);

        renderTableRows('#bmc-growth-sector-mes', growth.sector_mes || [], [
            { value: (row) => row.name || row.sector || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatNumber(row.previous, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true },
            { value: (row) => formatNumber(row.var_abs, 2) + ' MM' }
        ]);

        renderTableRows('#bmc-scb-acum-table', tables.ranking_scb_acumulado || [], [
            { value: (row) => row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatPercent(row.share, 2) },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-scb-mes-table', tables.ranking_scb_mes || [], [
            { value: (row) => row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatPercent(row.share, 2) },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-sector-total-acum-table', tables.ranking_sector_total_acumulado || [], [
            { value: (row) => row.name || row.sector || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatPercent(row.share, 2) },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-sector-total-mes-table', tables.ranking_sector_total_mes || [], [
            { value: (row) => row.name || row.sector || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => formatPercent(row.share, 2) },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-sector-scb-acum-table', tables.ranking_sector_scb_acumulado || [], [
            { value: (row) => row.sector || 'N/D' },
            { value: (row) => row.scb || row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-sector-scb-mes-table', tables.ranking_sector_scb_mes || [], [
            { value: (row) => row.sector || 'N/D' },
            { value: (row) => row.scb || row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-products-sector-table', products || [], [
            { value: (row) => row.sector || 'N/D' },
            { value: (row) => row.producto || 'N/D' },
            { value: (row) => formatNumber(row.monto_millones || 0, 2) + ' MM' },
            { value: (row) => formatPercent(row.participacion_pct || 0, 2) },
            { value: (row) => renderVariation(row.variacion_pct || 0), html: true }
        ]);

        renderTableRows('#bmc-repos-subyacente-acum-table', tables.repos_cdm_subyacente_acumulado || [], [
            { value: (row) => row.name || row.subyacente || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-repos-subyacente-mes-table', tables.repos_cdm_subyacente_mes || [], [
            { value: (row) => row.name || row.subyacente || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-repos-scb-acum-table', tables.repos_cdm_scb_acumulado || [], [
            { value: (row) => row.scb || row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);

        renderTableRows('#bmc-repos-scb-mes-table', tables.repos_cdm_scb_mes || [], [
            { value: (row) => row.scb || row.name || 'N/D' },
            { value: (row) => formatNumber(row.actual, 2) + ' MM' },
            { value: (row) => renderVariation(row.var_pct), html: true }
        ]);
    }

    function buildSummaryList(payload) {
        const data = payload && payload.data ? payload.data : payload;
        if (!data || typeof data !== 'object') {
            return ['<li class="list-group-item">Sin datos</li>'];
        }

        const items = [];
        Object.keys(data).forEach((key) => {
            const value = data[key];
            if (typeof value !== 'number') {
                return;
            }
            const lowerKey = key.toLowerCase();
            let formatted = formatNumber(value, 2);
            if (lowerKey.includes('comision') || lowerKey.includes('commission')) {
                formatted = formatCurrency(value);
            } else if (lowerKey.includes('margen')) {
                formatted = formatRatio(value);
            } else if (lowerKey.includes('participacion') || lowerKey.includes('share') || lowerKey.includes('crecimiento')) {
                formatted = formatPercent(value, 2);
            }
            items.push('<li class="list-group-item">' + key + ': ' + formatted + '</li>');
        });

        return items.length ? items : ['<li class="list-group-item">Sin datos</li>'];
    }

    function buildCsvFromRows(rows) {
        const header = [
            'Posicion',
            'SCB',
            'Participacion',
            'Volumen_Millones',
            'Crecimiento',
            'Comision_COP',
            'Negociado',
            'Margen_Porcentaje'
        ];

        const lines = [header.join(',')];

        rows.forEach((row) => {
            const values = [
                row.position,
                row.name,
                row.share,
                row.volume,
                row.growth,
                row.commission,
                row.negotiated,
                row.margin * 100
            ];

            const formatted = values.map((value, idx) => {
                if (value === null || value === undefined) {
                    return '""';
                }

                if (typeof value === 'number' && Number.isFinite(value)) {
                    let output;
                    if (idx === 0) {
                        output = Math.round(value).toString();
                    } else if (idx === 2 || idx === 4) {
                        output = value.toFixed(6);
                    } else if (idx === 3) {
                        output = value.toFixed(4);
                    } else if (idx === 7) {
                        output = value.toFixed(5);
                    } else {
                        output = value.toFixed(2);
                    }
                    return '"' + output + '"';
                }

                return '"' + String(value).replace(/"/g, '""') + '"';
            });

            lines.push(formatted.join(','));
        });

        return lines.join('\n');
    }

    function downloadCsv(filename, csvText) {
        const blob = new Blob([csvText], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function bindEvents() {
        $('.bmc-refresh').on('click', function () {
            runPageLoad();
        });

        if ($('#bmc-year').length) {
            const saved = storageGet('dashboard_filters', null);
            if (saved) {
                $('#bmc-year').val(saved.year);
                $('#bmc-limit').val(saved.limit);
                $('#bmc-period').val(saved.period);
            }
            $('#bmc-year, #bmc-limit, #bmc-period').on('change', loadDashboard);
        }

        if ($('#bmc-compare-scbs').length) {
            const saved = storageGet('comparativa_filters', null);
            if (saved) {
                $('#bmc-compare-period').val(saved.period || 12);
                $('#bmc-compare-window').val(saved.windowSize || 6);
                $('#bmc-compare-year').val(saved.year || config.defaultYear);
            }

            $('#bmc-compare-scbs').select2({
                placeholder: 'Seleccione SCB',
                width: '100%'
            });

            $('.bmc-compare-run').on('click', loadComparativa);
            $('#bmc-compare-period, #bmc-compare-window, #bmc-compare-year').on('change', loadComparativa);
        }

        if ($('#bmc-sector-year').length) {
            const saved = storageGet('sectores_filters', null);
            if (saved) {
                $('#bmc-sector-year').val(saved.year || config.defaultYear);
                $('#bmc-sector-filter').val(saved.filter || 'all');
            }
            $('#bmc-sector-year, #bmc-sector-filter').on('change', loadSectores);
        }

        if ($('#bmc-temporal-year').length) {
            const saved = storageGet('temporal_filters', null);
            if (saved) {
                $('#bmc-temporal-year').val(saved.year || config.defaultYear);
            }
            $('#bmc-temporal-year').on('change', loadTemporal);
        }

        if ($('#bmc-report-year').length) {
            const saved = storageGet('reportes_filters', null);
            if (saved) {
                $('#bmc-report-year').val(saved.year || config.defaultYear);
            }
            $('#bmc-report-year').on('change', loadReportes);

            $('#bmc-export-csv').on('click', async function () {
                const year = $('#bmc-report-year').val() || config.defaultYear;

                if (directApi) {
                    try {
                        const reportData = await apiGet('/reports', { limit: 200, year });
                        const rows = extractReportRows(reportData).map(normalizeReportRow);
                        const csv = buildCsvFromRows(rows);
                        const filename = 'benchmark_reportes_' + year + '.csv';
                        downloadCsv(filename, csv);
                    } catch (err) {
                        showError(err.message || 'No se pudo generar CSV');
                    }
                    return;
                }

                window.location.href = (config.apiBase || '/api/bi/benchmark') + '/export/csv?year=' + year;
            });

            $('#bmc-export-pdf').on('click', async function () {
                if (directApi) {
                    showError('Export PDF pendiente');
                    return;
                }
                try {
                    await apiGet('/export/pdf');
                } catch (err) {
                    showError(err.message || 'Export PDF pendiente');
                }
            });

            $('#bmc-export-excel').on('click', async function () {
                if (directApi) {
                    showError('Export Excel pendiente');
                    return;
                }
                try {
                    await apiGet('/export/excel');
                } catch (err) {
                    showError(err.message || 'Export Excel pendiente');
                }
            });

            $('#bmc-analyze-form').on('submit', async function (event) {
                event.preventDefault();
                const $form = $(this);
                const $progress = $('#bmc-analyze-progress');
                const $progressBar = $progress.find('.bmc-progress-bar');
                const $progressValue = $progress.find('.bmc-progress-value');
                const $progressLabel = $progress.find('.bmc-progress-label');
                const $progressTrack = $progress.find('.bmc-progress-track');
                const $submit = $form.find('button[type="submit"]');
                let processing = false;

                const setProgress = (percent, label) => {
                    const safeValue = Math.min(100, Math.max(0, Math.round(percent)));
                    if (label) {
                        $progressLabel.text(label);
                    }
                    $progressValue.text(safeValue + '%');
                    $progressBar.css('width', safeValue + '%');
                    if ($progressTrack.length) {
                        $progressTrack.attr('aria-valuenow', safeValue);
                    }
                };

                const fileInput = $('#bmc-analyze-file')[0];
                if (!fileInput || !fileInput.files.length) {
                    showError('Seleccione un archivo');
                    return;
                }

                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                formData.append('usuario', $('#bmc-analyze-user').val() || '');

                try {
                    processing = false;
                    $progress.removeClass('d-none is-processing');
                    setProgress(0, 'Subiendo archivo...');
                    $submit.prop('disabled', true);

                    const result = await apiPost('/analyze', formData, {
                        timeout: analyzeTimeoutMs,
                        onUploadProgress: function (event) {
                            if (!event || !event.total) {
                                return;
                            }
                            const percent = Math.round((event.loaded * 100) / event.total);
                            if (percent >= 100) {
                                if (!processing) {
                                    processing = true;
                                    $progress.addClass('is-processing');
                                    setProgress(100, 'Procesando analisis...');
                                }
                            } else {
                                setProgress(Math.min(percent, 95), 'Subiendo archivo...');
                            }
                        }
                    });
                    $('#bmc-analyze-result').text(JSON.stringify(result, null, 2));
                    showSuccess('Archivo analizado');
                    $progress.removeClass('is-processing');
                    setProgress(100, 'Completado');
                    setTimeout(() => {
                        $progress.addClass('d-none');
                    }, 1200);
                } catch (err) {
                    showError(err.message);
                    $progress.removeClass('is-processing');
                    setProgress(100, 'Error en analisis');
                } finally {
                    $submit.prop('disabled', false);
                }
            });
        }

        if ($('#bmc-analysis-year').length) {
            const saved = storageGet('analysis_filters', null);
            if (saved) {
                $('#bmc-analysis-year').val(saved.year || config.defaultYear);
            }

            $('#bmc-analysis-year').on('change', loadAnalysis);
            $('#bmc-analysis-report').on('change', function () {
                const year = $('#bmc-analysis-year').val() || config.defaultYear;
                const reportId = $(this).val();
                loadAnalysisData(reportId, year);
            });

            $('#bmc-analysis-scbs').on('change', function () {
                renderAnalysisCompetition(state.analysis || {});
                const savedFilters = storageGet('analysis_filters', {});
                const year = $('#bmc-analysis-year').val() || config.defaultYear;
                const reportId = $('#bmc-analysis-report').val();
                storageSet('analysis_filters', {
                    year,
                    reportId,
                    scbs: $(this).val() || savedFilters.scbs || []
                });
            });
        }

        if ($('#bmc-product-year').length) {
            const saved = storageGet('productos_filters', null);
            if (saved) {
                $('#bmc-product-year').val(saved.year || config.defaultYear);
                $('#bmc-product-limit').val(saved.limit || 20);
            }

            $('#bmc-product-year, #bmc-product-limit').on('change', loadProductos);
            $('#bmc-product-period').on('change', function () {
                const limit = $('#bmc-product-limit').val() || 20;
                const periodId = $(this).val();
                storageSet('productos_filters', {
                    year: $('#bmc-product-year').val() || config.defaultYear,
                    limit,
                    period: periodId
                });
                loadProductPeriod(periodId, limit);
            });

            $('#bmc-product-chart-type, #bmc-product-chart-metric, #bmc-product-chart-source, #bmc-product-chart-top').on('change', function () {
                updateProductChart();
            });
        }
    }

    async function loadProductos() {
        const year = $('#bmc-product-year').val() || config.defaultYear;
        const limit = $('#bmc-product-limit').val() || 20;

        storageSet('productos_filters', { year, limit, period: $('#bmc-product-period').val() });

        try {
            const periods = await apiGet('/products/periods', { year });
            updateProductPeriodOptions(periods);

            await Promise.all([
                loadProductGeneral(year, limit),
                loadProductMonthly(year, limit)
            ]);

            const periodId = $('#bmc-product-period').val();
            await loadProductPeriod(periodId, limit);
            updateProductChart();
            updateTimestamp();
        } catch (err) {
            showError(err.message);
        }
    }

    function updateProductPeriodOptions(periods) {
        const $select = $('#bmc-product-period');
        if (!$select.length) return;

        const saved = storageGet('productos_filters', null);
        const savedPeriod = saved ? saved.period : '';

        $select.empty();
        if (!Array.isArray(periods) || !periods.length) {
            $select.append('<option value="">Sin reportes</option>');
            return;
        }

        periods.forEach((row) => {
            const label = row.periodo || row.period || row.label || 'Periodo';
            $select.append('<option value="' + row.id + '">' + label + '</option>');
        });

        if (savedPeriod) {
            $select.val(savedPeriod);
        } else {
            $select.val(periods[periods.length - 1].id);
        }
    }

    async function loadProductGeneral(year, limit) {
        const data = await apiGet('/products', { year, limit });
        const items = (data && data.items) ? data.items : data;
        state.product.general = items || [];
        renderProductTable('#bmc-products-general', items || []);
    }

    async function loadProductPeriod(reportId, limit) {
        if (!reportId) {
            renderProductTable('#bmc-products-period', []);
            return;
        }
        const data = await apiGet('/products', { report_id: reportId, limit });
        const items = (data && data.items) ? data.items : data;
        state.product.period = items || [];
        renderProductTable('#bmc-products-period', items || []);
        updateProductChart();
    }

    async function loadProductMonthly(year, limit) {
        const data = await apiGet('/products/monthly', { year, limit });
        const items = Array.isArray(data) ? data : [];
        state.product.monthly = items;
        renderProductMonthlyTable('#bmc-products-monthly', items);
    }

    function renderProductTable(selector, items) {
        const $table = $(selector);
        if (!$table.length) return;

        if ($.fn.dataTable.isDataTable($table)) {
            $table.DataTable().clear().destroy();
        }

        const data = (items || []).map((item) => {
            return [
                item.producto || 'N/D',
                item.sector || 'N/D',
                formatNumber(item.monto_millones || 0, 2) + ' MM',
                formatPercent(item.participacion_pct || 0, 2),
                renderVariation(item.variacion_pct || 0)
            ];
        });

        $table.DataTable({
            data,
            pageLength: 10,
            order: [[2, 'desc']]
        });
    }

    function renderProductMonthlyTable(selector, items) {
        const $table = $(selector);
        if (!$table.length) return;

        if ($.fn.dataTable.isDataTable($table)) {
            $table.DataTable().clear().destroy();
        }

        const data = (items || []).map((item) => {
            return [
                item.periodo || 'N/D',
                item.producto || 'N/D',
                item.sector || 'N/D',
                formatNumber(item.monto_millones || 0, 2) + ' MM',
                formatPercent(item.participacion_pct || 0, 2),
                renderVariation(item.variacion_pct || 0)
            ];
        });

        $table.DataTable({
            data,
            pageLength: 12,
            order: [[0, 'asc']]
        });
    }

    function getProductMetricValue(item, metric) {
        if (metric === 'participacion') return safeNumber(item.participacion_pct || 0);
        if (metric === 'variacion') return safeNumber(item.variacion_pct || 0);
        return safeNumber(item.monto_millones || 0);
    }

    function aggregateByProduct(items, metric) {
        const grouped = {};
        (items || []).forEach((item) => {
            const key = (item.producto || 'N/D') + '|' + (item.sector || 'N/D');
            if (!grouped[key]) {
                grouped[key] = {
                    producto: item.producto || 'N/D',
                    sector: item.sector || 'N/D',
                    total: 0,
                    count: 0
                };
            }
            grouped[key].total += getProductMetricValue(item, metric);
            grouped[key].count += 1;
        });
        return Object.values(grouped).map((item) => ({
            producto: item.producto,
            sector: item.sector,
            valor: metric === 'monto' ? item.total : (item.total / Math.max(1, item.count))
        }));
    }

    function aggregateByPeriod(items, metric) {
        const grouped = {};
        (items || []).forEach((item) => {
            const periodo = item.periodo || 'Sin periodo';
            if (!grouped[periodo]) {
                grouped[periodo] = { total: 0, count: 0 };
            }
            grouped[periodo].total += getProductMetricValue(item, metric);
            grouped[periodo].count += 1;
        });

        const labels = Object.keys(grouped).sort((a, b) => parsePeriodoKey(a) - parsePeriodoKey(b));
        const values = labels.map((label) => {
            const data = grouped[label];
            return metric === 'monto' ? data.total : data.total / Math.max(1, data.count);
        });
        return { labels, values };
    }

    function updateProductChart() {
        const canvasId = 'bmc-products-chart';
        if (!document.getElementById(canvasId)) return;

        const type = $('#bmc-product-chart-type').val() || 'bar';
        const metric = $('#bmc-product-chart-metric').val() || 'monto';
        const source = $('#bmc-product-chart-source').val() || 'general';
        const top = parseInt($('#bmc-product-chart-top').val() || '8', 10);

        let labels = [];
        let values = [];
        let datasetLabel = '';

        const metricLabel = metric === 'monto'
            ? 'Volumen (MM)'
            : metric === 'participacion'
                ? 'Participacion (%)'
                : 'Variacion (%)';

        if (source === 'monthly' && type === 'line') {
            const trend = aggregateByPeriod(state.product.monthly, metric);
            labels = trend.labels;
            values = trend.values.map((value) => Math.round(value * 100) / 100);
            datasetLabel = metricLabel + ' por periodo';
        } else {
            let items = [];
            if (source === 'general') {
                items = state.product.general || [];
                items = items.map((item) => ({
                    producto: item.producto || 'N/D',
                    sector: item.sector || 'N/D',
                    valor: getProductMetricValue(item, metric)
                }));
            } else if (source === 'period') {
                items = state.product.period || [];
                items = items.map((item) => ({
                    producto: item.producto || 'N/D',
                    sector: item.sector || 'N/D',
                    valor: getProductMetricValue(item, metric)
                }));
            } else {
                items = aggregateByProduct(state.product.monthly, metric);
            }

            items.sort((a, b) => b.valor - a.valor);
            const topItems = items.slice(0, top);
            labels = topItems.map((item) => item.producto);
            values = topItems.map((item) => Math.round(item.valor * 100) / 100);
            datasetLabel = metricLabel + ' - Top ' + top;
        }

        if (!labels.length) {
            labels = ['Sin datos'];
            values = [0];
        }

        renderProductChart(canvasId, type, labels, datasetLabel, values);
    }

    async function loadCompareOptions() {
        try {
            const reportData = await apiGet('/reports', { limit: 50, year: config.defaultYear });
            const rows = extractReportRows(reportData).map(normalizeReportRow);
            const $select = $('#bmc-compare-scbs');
            if (!$select.length) return;

            $select.empty();
            rows.forEach((row) => {
                $select.append('<option value="' + row.id + '">' + row.name + '</option>');
            });

            const saved = storageGet('comparativa_filters', null);
            if (saved && saved.selected && saved.selected.length) {
                $select.val(saved.selected).trigger('change');
            } else {
                const defaultIds = rows.filter((row) => isCorreagro(row.name)).map((row) => row.id);
                if (defaultIds.length) {
                    $select.val(defaultIds).trigger('change');
                }
            }
        } catch (err) {
            showError(err.message);
        }
    }

    function runPageLoad() {
        const page = getPage();
        if (page === 'dashboard') {
            loadDashboard();
        } else if (page === 'comparativa') {
            loadCompareOptions().then(loadComparativa);
        } else if (page === 'sectores') {
            loadSectores();
        } else if (page === 'temporal') {
            loadTemporal();
        } else if (page === 'productos') {
            loadProductos();
        } else if (page === 'reportes') {
            loadReportes();
            loadAnalysis();
        }
    }

    $(document).ready(function () {
        bindEvents();
        runPageLoad();
    });
})(jQuery);

