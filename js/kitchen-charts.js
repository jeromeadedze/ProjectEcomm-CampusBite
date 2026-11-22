/**
 * Bar charts for the kitchen dashboards. One series per chart, one axis, a tooltip on hover or keyboard focus.
 *
 *   CBCharts.bars(element, rows, { value: 'orders', money: false, label: 'Orders' })
 *   rows: [{ label: 'Mon 17 Nov', short: 'Mon', tick: true, orders: 12, earnings: 340.5 }, ...]
 * Charts redraw when the window is resized.
 */
(function () {
    var NS = 'http://www.w3.org/2000/svg';
    var tip = null;

    function el(name, attrs) {
        var e = document.createElementNS(NS, name);
        for (var k in attrs) e.setAttribute(k, attrs[k]);
        return e;
    }
    function money(n) {
        return 'GH₵ ' + Number(n).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function shortMoney(n) {
        return n >= 1000 ? (n / 1000).toLocaleString('en-GH', { maximumFractionDigits: 1 }) + 'k' : String(Math.round(n));
    }
    // A clean top value: counts get a multiple of 4 so every gridline is a whole number
    function niceMax(v, isMoney) {
        if (!isMoney) return Math.max(4, Math.ceil(v / 4) * 4);
        if (v <= 0) return 100;
        var p = Math.pow(10, Math.floor(Math.log10(v))), n = v / p;
        return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10) * p;
    }
    function tooltip() {
        if (!tip) {
            tip = document.createElement('div');
            tip.className = 'ka-tooltip';
            tip.setAttribute('role', 'status');
            tip.hidden = true;
            document.body.appendChild(tip);
        }
        return tip;
    }

    function bars(box, rows, opts) {
        var key = opts.value, isMoney = !!opts.money;
        // Draw at roughly the size the card shows it, so labels stay readable in narrow cards too
        var W = Math.max(280, Math.round(box.clientWidth || 680)), H = opts.height || 230, L = isMoney ? 46 : 30, R = 6, T = 10, B = 26;
        var max = niceMax(Math.max.apply(null, rows.map(function (r) { return r[key]; })), isMoney);
        var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': opts.label || '' });
        var plotW = W - L - R, plotH = H - T - B, slot = plotW / rows.length;
        var barW = Math.min(opts.maxBar || 34, Math.max(2, slot - 4));

        for (var g = 0; g <= 4; g++) {
            var y = T + plotH - plotH * g / 4, v = max * g / 4;
            svg.appendChild(el('line', { x1: L, x2: W - R, y1: y, y2: y, class: g === 0 ? 'ka-axis' : 'ka-gridline' }));
            var t = el('text', { x: L - 6, y: y + 4, 'text-anchor': 'end', class: 'ka-tick' });
            t.textContent = isMoney ? shortMoney(v) : Math.round(v);
            svg.appendChild(t);
        }

        var tipEl = tooltip();
        rows.forEach(function (r, i) {
            var x = L + i * slot + (slot - barW) / 2, h = r[key] / max * plotH, y0 = T + plotH;
            if (h > 0) {
                // 4px rounded top, square on the baseline
                var rad = Math.min(4, barW / 2, h), y1 = y0 - h;
                svg.appendChild(el('path', { class: 'ka-bar' + (r.highlight ? ' is-now' : ''), d: 'M' + x + ',' + y0 + 'V' + (y1 + rad) + 'Q' + x + ',' + y1 + ' ' + (x + rad) + ',' + y1 + 'H' + (x + barW - rad) + 'Q' + (x + barW) + ',' + y1 + ' ' + (x + barW) + ',' + (y1 + rad) + 'V' + y0 + 'Z' }));
            }
            if (r.tick) {
                var lab = el('text', { x: x + barW / 2, y: H - 7, 'text-anchor': 'middle', class: 'ka-tick' });
                lab.textContent = r.short || r.label;
                svg.appendChild(lab);
            }
            var detail = opts.detail ? opts.detail(r) : (isMoney ? money(r[key]) : r[key]);
            var hit = el('rect', { x: L + i * slot, y: T, width: slot, height: plotH, class: 'ka-hit', tabindex: '0', 'aria-label': r.label + ': ' + detail.replace(/<[^>]+>/g, ' ') });
            var show = function () {
                tipEl.innerHTML = '<b>' + r.label + '</b>' + detail;
                tipEl.hidden = false;
                var b = hit.getBoundingClientRect();
                tipEl.style.left = Math.min(window.innerWidth - tipEl.offsetWidth - 12, Math.max(12, b.left + b.width / 2 - tipEl.offsetWidth / 2)) + 'px';
                tipEl.style.top = (b.top + window.scrollY - tipEl.offsetHeight - 8) + 'px';
                hit.classList.add('is-hover');
            };
            var hide = function () { tipEl.hidden = true; hit.classList.remove('is-hover'); };
            hit.addEventListener('mouseenter', show);
            hit.addEventListener('focus', show);
            hit.addEventListener('mouseleave', hide);
            hit.addEventListener('blur', hide);
            svg.appendChild(hit);
        });
        box.replaceChildren(svg);
        if (!box._cbResize) {
            var t;
            box._cbResize = true;
            window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(function () { bars(box, rows, box._cbOpts); }, 150); });
        }
        box._cbOpts = opts;
    }

    window.CBCharts = { bars: bars, money: money };
})();
