// page-index.js — Homepage booking widget logic
// Requires: airports-data.js, shared.js (fmt, generateBookingRef)
// fmt returns USD strings: '$70', '$95', etc.

var BW = { airport: null, flightType: 'dom', addons: {} };

// USD add-on prices
var ADDONS = {
  porter:     { id:'porter',     label:'Extra Porter',          icon:'\uD83E\uDDF3', usd:15, desc:'Up to 3 bags per porter' },
  wheelchair: { id:'wheelchair', label:'Wheelchair Assistance', icon:'\u267F',        usd:15, desc:'Per person' }
};

// ── Hero slideshow
(function(){
  var slides  = document.querySelectorAll('.hero-slide');
  var cur     = 0;
  var dotsEl  = document.getElementById('slide-dots');
  if (!dotsEl || !slides.length) return;
  dotsEl.innerHTML = Array.from(slides).map(function(_, i) {
    return '<div class="sdot' + (i === 0 ? ' active' : '') + '" onclick="goSlide(' + i + ')"></div>';
  }).join('');
  window.goSlide = function(n) {
    slides[cur].classList.remove('active');
    document.querySelectorAll('.sdot')[cur].classList.remove('active');
    cur = n;
    slides[cur].classList.add('active');
    document.querySelectorAll('.sdot')[cur].classList.add('active');
  };
  setInterval(function(){ window.goSlide((cur + 1) % slides.length); }, 5000);
})();

// ── Marquee
(function(){
  var mt = document.getElementById('mtrack');
  if (!mt || typeof AIRPORTS === 'undefined') return;
  var items = AIRPORTS.map(function(a) {
    return '<span class="mit"><span class="dot"></span>' + a.city + ' &middot; ' + a.code + '</span>';
  }).join('');
  mt.innerHTML = items + items;
})();

// ── Hero search dropdown
function heroSearch(q) {
  var dd = document.getElementById('sdrop');
  if (!dd) return;
  if (!q || q.length < 2) { dd.style.display = 'none'; return; }
  var lq  = q.toLowerCase();
  var res = AIRPORTS.filter(function(a) {
    return a.city.toLowerCase().indexOf(lq) > -1
        || a.name.toLowerCase().indexOf(lq) > -1
        || a.code.toLowerCase().indexOf(lq) > -1;
  }).slice(0, 8);
  if (!res.length) { dd.style.display = 'none'; return; }
  dd.style.display = 'block';
  dd.innerHTML = res.map(function(a) {
    return '<div class="sdi" onclick="bwSelectAirport(\'' + a.code + '\')">'
      + '<div><div class="sdi-city">' + a.city + '</div><div class="sdi-name">' + a.name + '</div></div>'
      + '<div class="sdi-right"><span class="sdi-price">' + fmt(a.dom) + '</span><span class="sdi-code">' + a.code + '</span></div>'
      + '</div>';
  }).join('');
}
document.addEventListener('click', function(e) {
  var dd  = document.getElementById('sdrop');
  var inp = document.getElementById('hero-search');
  if (dd && !dd.contains(e.target) && e.target !== inp) dd.style.display = 'none';
});

// ── Step navigation
function bwGoStep(n) {
  if (n === 2 && !BW.airport) return;
  if (n === 3 && !BW.airport) return;
  [1, 2, 3].forEach(function(i) {
    document.getElementById('panel' + i).classList.toggle('active', i === n);
    var tab = document.getElementById('tab' + i);
    tab.classList.toggle('active', i === n);
    if (i < n) tab.classList.add('done'); else if (i > n) tab.classList.remove('done');
  });
  if (n === 2) bwUpdatePrice();
  if (n === 3) bwUpdatePriceBar();
}

// ── Select airport
function bwSelectAirport(code) {
  var ap = AIRPORTS.find(function(a){ return a.code === code; });
  if (!ap) return;
  BW.airport = ap; BW.addons = {};
  document.getElementById('sel-ap-name').textContent = ap.city + ' \u2014 ' + ap.name;
  document.getElementById('sel-ap-sub').textContent  = ap.code + ' \u00b7 ' + ap.state;
  document.getElementById('sel-ap-pill').classList.add('show');
  document.getElementById('search-area').style.display = 'none';
  document.getElementById('next1').disabled = false;
  var dd  = document.getElementById('sdrop');  if (dd)  dd.style.display = 'none';
  var inp = document.getElementById('hero-search'); if (inp) inp.value = '';
  setTimeout(function(){ bwGoStep(2); }, 300);
}

function bwClearAirport() {
  BW.airport = null; BW.addons = {};
  document.getElementById('sel-ap-pill').classList.remove('show');
  document.getElementById('search-area').style.display = 'block';
  document.getElementById('next1').disabled = true;
  bwGoStep(1);
}

function bwSetFlight(type) {
  BW.flightType = type;
  document.getElementById('bw-btn-dom').classList.toggle('active',  type === 'dom');
  document.getElementById('bw-btn-intl').classList.toggle('active', type === 'intl');
  bwUpdatePrice();
}

// ── Render addon checkboxes
function bwRenderAddons() {
  var ap   = BW.airport;
  var list = document.getElementById('addon-list');
  if (!list || !ap) return;
  var html = '';
  Object.keys(ADDONS).forEach(function(key) {
    var addon = ADDONS[key];
    if (key === 'wheelchair' && !ap.buggy) return;  // wheelchair only at airports that have buggy service
    var checked = BW.addons[key] ? 'checked' : '';
    html += '<label style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(212,175,55,.15);border-radius:10px;padding:10px 14px;cursor:pointer;margin-bottom:6px">'
      + '<input type="checkbox" onchange="bwToggleAddon(\'' + key + '\',this.checked)" ' + checked + ' style="width:16px;height:16px;accent-color:var(--gl);cursor:pointer;flex-shrink:0">'
      + '<div style="flex:1"><div style="font-size:.85rem;font-weight:600;color:var(--w)">' + addon.icon + ' ' + addon.label + '</div>'
      + '<div style="font-size:.7rem;color:var(--sl);margin-top:1px">' + addon.desc + '</div></div>'
      + '<span style="font-weight:700;color:var(--gb);white-space:nowrap">+' + fmt(addon.usd) + '</span>'
      + '</label>';
  });
  list.innerHTML = html || '<div style="font-size:.78rem;color:var(--sl);padding:4px 0">No add-ons available for this airport.</div>';
  bwUpdateAddonTotal();
}

function bwToggleAddon(key, checked) {
  if (checked) BW.addons[key] = ADDONS[key].usd; else delete BW.addons[key];
  bwUpdateAddonTotal();
}

function bwUpdateAddonTotal() {
  if (!BW.airport) return;
  var base  = BW.flightType === 'dom' ? BW.airport.dom : (BW.airport.intl || 0);
  var extra = Object.values(BW.addons).reduce(function(s, v){ return s + v; }, 0);
  var row   = document.getElementById('addon-total-row');
  var tot   = document.getElementById('addon-total');
  if (!row || !tot) return;
  if (extra > 0) { row.style.display = 'flex'; tot.textContent = fmt(base + extra); }
  else             row.style.display = 'none';
}

// ── Update price display (step 2)
function bwUpdatePrice() {
  if (!BW.airport) return;
  var ap      = BW.airport;
  var intlBtn = document.getElementById('bw-btn-intl');
  var pdbox   = document.getElementById('bw-pdbox');
  var priceEl = document.getElementById('bw-pd-price');
  var noteEl  = document.getElementById('bw-pd-note');
  var next2   = document.getElementById('next2');
  document.getElementById('step2-sub').textContent = ap.city + ' \u2014 ' + ap.code + ' \u00b7 ' + ap.state;
  if (!ap.intl) {
    intlBtn.disabled = true;
    intlBtn.title    = 'No international flights at ' + ap.city;
    if (BW.flightType === 'intl') {
      BW.flightType = 'dom';
      document.getElementById('bw-btn-dom').classList.add('active');
      intlBtn.classList.remove('active');
    }
  } else {
    intlBtn.disabled = false;
    intlBtn.title    = '';
  }
  var p = BW.flightType === 'dom' ? ap.dom : ap.intl;
  if (!p) {
    pdbox.classList.add('unavail');
    priceEl.innerHTML = '<span class="pdbox-na">Not available</span>';
    noteEl.textContent = 'No international flights at ' + ap.city;
    next2.disabled = true;
  } else {
    pdbox.classList.remove('unavail');
    priceEl.textContent = fmt(p);
    noteEl.textContent  = 'Per person \u00b7 Per service \u00b7 USD pricing';
    next2.disabled      = false;
  }
  bwRenderAddons();
}

// ── Update price bar + hidden fields (step 3)
function bwUpdatePriceBar() {
  if (!BW.airport) return;
  var ap         = BW.airport;
  var p          = BW.flightType === 'dom' ? ap.dom : ap.intl;
  var typeLabel  = BW.flightType === 'dom' ? 'Domestic' : 'International';
  var addonExtra = Object.values(BW.addons).reduce(function(s, v){ return s + v; }, 0);
  var addonNames = Object.keys(BW.addons).map(function(k){ return ADDONS[k].label; }).join(', ');
  var grand      = (p || 0) + addonExtra;
  document.getElementById('pb-detail').textContent = ap.city + ' Airport (' + ap.code + ') \u00b7 ' + typeLabel;
  document.getElementById('pb-price').textContent  = fmt(grand);
  var ref      = (typeof generateBookingRef === 'function') ? generateBookingRef() : ('TB-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 9000) + 1000));
  var priceStr = p ? ('$' + grand + ' USD (base $' + p + (addonNames ? ' + ' + addonNames : '') + ')') : 'N/A';
  document.getElementById('bw-f-airport').value = ap.city + ' \u2014 ' + ap.name + ' (' + ap.code + ')';
  document.getElementById('bw-f-type').value    = typeLabel;
  document.getElementById('bw-f-price').value   = priceStr;
  document.getElementById('bw-f-ref').value     = ref;
  document.getElementById('bw-f-url').value     = window.location.href;
}

// ── Submit booking
function bwSubmit(e) {
  e.preventDefault();
  var btn = document.querySelector('#bw-form .bw-submit');
  if (btn) { btn.disabled = true; btn.textContent = 'Sending\u2026'; }

  var d = new FormData(document.getElementById('bw-form'));
  var addonLines = [];
  Object.keys(ADDONS).forEach(function(key) {
    var cb = document.querySelector('input[name="addon_' + key + '"]');
    if (cb && cb.checked) addonLines.push(ADDONS[key].label + ' (+$' + ADDONS[key].usd + ')');
  });

  var payload = {
    bookingRef:  d.get('bookingRef') || 'TB-REF',
    airport:     d.get('airport'),
    flightType:  d.get('flightType'),
    price:       d.get('price'),
    addons:      addonLines.length ? addonLines.join(', ') : 'None',
    pageUrl:     d.get('pageUrl') || window.location.href,
    firstName:   d.get('firstName'),
    lastName:    d.get('lastName'),
    email:       d.get('email'),
    phone:       d.get('phone'),
    serviceType: d.get('serviceType'),
    passengers:  d.get('passengers'),
    flightNo:    d.get('flightNo') || '',
    travelDate:  d.get('travelDate'),
    flightTime:  d.get('flightTime'),
    specialReq:  d.get('specialReq') || ''
  };

  fetch('send-booking.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload)
  })
  .then(function(r){ return r.json(); })
  .then(function(res) {
    // Check success flag — don't show success if emails failed
    if (res.success === false) {
      if (btn) { btn.disabled = false; btn.textContent = '\u2708 Send Booking Request'; }
      showBwError('Our team has been notified. Reference: ' + (res.ref || payload.bookingRef) + '. Please also email <a href="mailto:sales@aerogreetindia.com" style="color:#fca5a5">sales@aerogreetindia.com</a> to confirm.');
      return;
    }
    document.getElementById('bw-form').style.display    = 'none';
    document.getElementById('bw-success').style.display = 'block';
    var refEl = document.getElementById('bw-success-ref');
    if (refEl) refEl.textContent = res.ref || payload.bookingRef;
  })
  .catch(function() {
    if (btn) { btn.disabled = false; btn.textContent = '\u2708 Send Booking Request'; }
    showBwError('Could not connect to our server. Please email <a href="mailto:sales@aerogreetindia.com" style="color:#fca5a5">sales@aerogreetindia.com</a> directly with reference: <strong>' + (d.get('bookingRef') || '') + '</strong>');
  });
}

function showBwError(msg) {
  var errDiv = document.getElementById('bw-form-error');
  if (!errDiv) {
    errDiv = document.createElement('div');
    errDiv.id = 'bw-form-error';
    errDiv.style.cssText = 'background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);color:#f87171;border-radius:9px;padding:12px 16px;font-size:.84rem;margin-top:10px;line-height:1.5;text-align:center';
    document.getElementById('bw-form').appendChild(errDiv);
  }
  errDiv.innerHTML = msg;
  errDiv.style.display = 'block';
}

// No currency hook needed — USD is the only currency.
