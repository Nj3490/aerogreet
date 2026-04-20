// page-airports.js — Airports listing page logic
// Requires: airports-data.js, shared.js (fmt)

var currentFilter = 'all';
var currentView   = 'grid';

function buildGrid() {
  var grid = document.getElementById('ap-grid');
  if (!grid || typeof AIRPORTS === 'undefined') return;
  grid.innerHTML = AIRPORTS.map(function(a) {
    return '<a href="airport.html?code=' + a.code + '" class="ap-card"'
      + ' data-search="' + (a.city + ' ' + a.name + ' ' + a.code + ' ' + a.state).toLowerCase() + '"'
      + ' data-intl="'   + (a.intl  ? '1' : '0') + '"'
      + ' data-buggy="'  + (a.buggy ? '1' : '0') + '">'
      + '<div class="ap-card-head">'
      + '<div><div class="ap-city">' + a.city + '</div><div class="ap-state">' + a.state + '</div></div>'
      + '<span class="ap-iata">' + a.code + '</span>'
      + '</div>'
      + '<div class="ap-name">' + a.name + '</div>'
      + '<div class="ap-prices">'
      + '<div class="apt-price-box"><span class="pbl">Domestic</span><span class="pbv" data-inr="' + a.dom + '">' + fmt(a.dom) + '</span></div>'
      + (a.intl
          ? '<div class="apt-price-box intl"><span class="pbl">International</span><span class="pbv" data-inr="' + a.intl + '">' + fmt(a.intl) + '</span></div>'
          : '<span class="ap-dom-only">Domestic only</span>')
      + '</div>'
      + '<span class="ap-book-btn">Book Greeter &#8594;</span>'
      + '</a>';
  }).join('');
}

function buildTable() {
  var tbody = document.getElementById('ap-tbody');
  if (!tbody || typeof AIRPORTS === 'undefined') return;
  tbody.innerHTML = AIRPORTS.map(function(a) {
    return '<tr'
      + ' data-search="' + (a.city + ' ' + a.name + ' ' + a.code + ' ' + a.state).toLowerCase() + '"'
      + ' data-intl="'   + (a.intl  ? '1' : '0') + '"'
      + ' data-buggy="'  + (a.buggy ? '1' : '0') + '">'
      + '<td><div class="td-city">' + a.city + '</div><div class="td-nm">' + a.name + '</div></td>'
      + '<td><span class="td-code">' + a.code + '</span></td>'
      + '<td style="font-size:.8rem;color:var(--sl)">' + a.state + '</td>'
      + '<td><span class="td-p" data-inr="' + a.dom + '">' + fmt(a.dom) + '</span></td>'
      + '<td>' + (a.intl ? '<span class="td-p td-intl" data-inr="' + a.intl + '">' + fmt(a.intl) + '</span>' : '<span class="td-na">N/A</span>') + '</td>'
      + '<td>' + (a.buggy ? '<span class="td-p" data-inr="' + a.buggy + '">' + fmt(a.buggy) + '</span>' : '<span class="td-na">\u2014</span>') + '</td>'
      + '<td><a href="airport.html?code=' + a.code + '" class="td-book">Book</a></td>'
      + '</tr>';
  }).join('');
}

function applyFilters() {
  var q     = (document.getElementById('srch').value || '').toLowerCase().trim();
  var f     = currentFilter;
  var items = currentView === 'grid'
    ? document.querySelectorAll('#ap-grid .ap-card')
    : document.querySelectorAll('#ap-tbody tr');
  var count = 0;
  items.forEach(function(el) {
    var matchQ = !q || (el.dataset.search || '').indexOf(q) > -1;
    var matchF = f === 'all'
      || (f === 'intl'  && el.dataset.intl  === '1')
      || (f === 'dom'   && el.dataset.intl  === '0')
      || (f === 'buggy' && el.dataset.buggy === '1');
    var show = matchQ && matchF;
    el.style.display = show ? '' : 'none';
    if (show) count++;
  });
  var ct = document.getElementById('ap-count');
  if (ct) ct.textContent = count;
  var nr = document.getElementById('no-results');
  if (nr) nr.style.display = count === 0 ? 'block' : 'none';
}

function doSearch() { applyFilters(); }

function doFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.ftab').forEach(function(b){ b.classList.remove('active'); });
  if (btn) btn.classList.add('active');
  applyFilters();
}

function setView(v) {
  currentView = v;
  var grid  = document.getElementById('ap-grid');
  var table = document.getElementById('ap-table');
  if (v === 'grid') { grid.style.display = '';      table.style.display = 'none'; }
  else              { grid.style.display = 'none';  table.style.display = 'block'; }
  document.getElementById('vt-grid').classList.toggle('active', v === 'grid');
  document.getElementById('vt-list').classList.toggle('active', v === 'list');
  applyFilters();
}

// Refresh all price displays — data-inr elements + data-base-inr elements
function refreshPrices() {
  document.querySelectorAll('[data-inr]').forEach(function(el) {
    var v = parseFloat(el.dataset.inr);
    if (!isNaN(v)) el.textContent = fmt(v);
  });
  document.querySelectorAll('[data-base-inr]').forEach(function(el) {
    var v = parseFloat(el.dataset.baseInr);
    if (!isNaN(v)) el.textContent = fmt(v);
  });
}

// No currency hook needed — USD is the only currency.

// Init
buildGrid();
buildTable();
applyFilters();
