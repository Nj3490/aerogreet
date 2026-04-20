// ============================================================
// shared.js — Aero Greet India
// USD pricing · Font picker · Light / Dark theme toggle · Accent colour picker
// ============================================================

/* ── USD formatting ─────────────────────────────────────────── */
function fmt(usd) {
  if (usd === null || usd === undefined || usd === '') return '';
  return '$' + Number(usd).toFixed(0);
}

/* ── Theme ──────────────────────────────────────────────────── */
var currentTheme = (function () {
  return localStorage.getItem('themePreference') || 'light';
})();

function applyTheme(theme) {
  currentTheme = theme;
  localStorage.setItem('themePreference', theme);
  document.documentElement.setAttribute('data-theme', theme);
  var btn = document.getElementById('theme-toggle-btn');
  if (btn) {
    btn.textContent = theme === 'dark' ? '\u2600 Light Mode' : '\uD83C\uDF19 Dark Mode';
    btn.title       = theme === 'dark' ? 'Switch to Light theme' : 'Switch to Dark theme';
  }
}

function toggleTheme() {
  applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
}

/* ── Accent colour picker ────────────────────────────────────── */
var COLOR_THEMES = {
  gold:   { label:'Gold',   gl:'#D4AF37', dark:'#B8960C', bright:'#F0C040', r:212, g:175, b:55  },
  aqua:   { label:'Aqua',   gl:'#0497D6', dark:'#0270A0', bright:'#05B8FF', r:4,   g:151, b:214 },
  purple: { label:'Purple', gl:'#6D35F0', dark:'#5020C0', bright:'#9B6EFF', r:109, g:53,  b:240 },
  pink:   { label:'Pink',   gl:'#F24B7A', dark:'#C0305A', bright:'#FF7AA0', r:242, g:75,  b:122 }
};

var currentColor = localStorage.getItem('ag_color') || 'gold';

function applyColor(colorId) {
  var c = COLOR_THEMES[colorId];
  if (!c) return;
  currentColor = colorId;
  localStorage.setItem('ag_color', colorId);

  var gp  = 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',.12)';
  var gp2 = 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',.08)';
  var br  = 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',.2)';
  var br2 = 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',.35)';
  var br3 = 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',.45)';
  var sh  = '0 10px 28px rgba(' + c.r + ',' + c.g + ',' + c.b + ',.45)';
  var sh2 = '0 4px 18px rgba(' + c.r + ',' + c.g + ',' + c.b + ',.4)';

  /* — Update all CSS variables on :root — */
  var root = document.documentElement;
  root.style.setProperty('--gl',         c.gl);
  root.style.setProperty('--gold',       c.dark);
  root.style.setProperty('--gb',         c.bright);
  root.style.setProperty('--gp',         gp);
  root.style.setProperty('--br',         br);
  /* Secondary-page variable names */
  root.style.setProperty('--gold-light', c.gl);
  root.style.setProperty('--gold-bright',c.bright);
  root.style.setProperty('--gold-pale',  gp);
  root.style.setProperty('--border',     br);

  /* — Inject overrides for hardcoded hex values — */
  var styleId = 'ag-color-override';
  var el = document.getElementById(styleId);
  if (!el) { el = document.createElement('style'); el.id = styleId; document.head.appendChild(el); }

  /* Light-theme gold overrides: use darkened shade for contrast on white */
  var lightGold = colorId === 'gold' ? '#9A6F00' : c.dark;

  el.textContent = [
    /* Nav "Book Now" button */
    '.nav-book{background:linear-gradient(135deg,' + c.bright + ',' + c.dark + ')!important;box-shadow:0 4px 20px ' + gp2.replace('.08','.35') + '!important}',
    /* Logo icon */
    '.logo-icon{background:linear-gradient(135deg,' + c.bright + ',' + c.dark + ')!important;box-shadow:' + sh2 + '!important}',
    '.nav-logo-icon{background:linear-gradient(135deg,' + c.gl + ',' + c.dark + ')!important}',
    /* Sidebar toggle button */
    '#sidebar-toggle{background:' + c.gl + '!important}',
    /* Gold buttons */
    '.btn-gold,.nav-cta,.ap-book-btn{background:linear-gradient(135deg,' + c.gl + ',' + c.dark + ')!important;box-shadow:none}',
    '.btn-gold:hover{box-shadow:' + sh + '!important}',
    '.sub-btn,.quote-btn,.bw-submit,.bw-next{background:linear-gradient(135deg,' + c.gl + ',' + c.dark + ')!important}',
    '.submit-btn,.btn-primary{background:linear-gradient(135deg,' + c.gl + ',' + c.dark + ')!important}',
    /* Hardcoded gold text — nav logo small */
    '.logo-text small,.nav-logo-text span{color:' + c.gl + '!important}',
    /* Footer col titles */
    '.fcol-title{color:' + c.gl + '!important}',
    /* Footer account label */
    '.f-acct-label{color:' + c.gl + '!important}',
    /* Footer link arrows */
    '.fla{color:' + c.gl + '!important}',
    /* Footer contact links */
    '.f-contact-list li a{color:' + c.gl + '!important}',
    '.f-copy a:hover,.flinks a:hover,.fcol ul li a:hover{color:' + c.gl + '!important}',
    /* Tags / badges */
    '.stag,.page-tag,.ap-tag span,.post-tag,.intl-tag,.section-tag{color:' + c.gl + '!important}',
    /* Step circles */
    '.step-c{border-color:' + c.gl + '!important;color:' + c.gl + '!important}',
    /* Stars / dots */
    '.tstars,.proof-stars{color:' + c.bright + '!important}',
    '.bdot{background:' + c.gb + '!important}',
    /* FAQ chevron */
    '.faq-ch{color:' + c.gl + '!important}',
    /* Bookwidget active step dot */
    '.bw-step-tab.active,.bw-step-tab.done{color:' + c.gl + '!important}',
    '.bw-step-tab.active .stn,.bw-step-tab.done .stn{background:' + gp + '!important}',
    /* Price values */
    '.pdbox-price,.hpp-price,.pb-price{color:' + c.bright + '!important}',
    '.cc-value,.cc-value a{color:' + c.gl + '!important}',
    /* Hardcoded rgba(212,175,55,...) gradients on cta-box/sections */
    '.post-cta-box{border-color:' + br + '!important}',
    /* About stat numbers */
    '.about-stat-num{color:' + c.gl + '!important}',
    /* Callout strong */
    '.post-callout strong{color:' + c.gl + '!important}',
    /* Price table header */
    '.post-price-table th{color:' + c.gl + '!important}',
    /* Intl stat numbers */
    '.intl-stat-num{color:' + c.gl + '!important}',
    /* Team role */
    '.team-role,.tl-year{color:' + c.gl + '!important}',
    /* Table book button */
    '.td-book{background:linear-gradient(135deg,' + c.gl + ',' + c.dark + ')!important}',
    /* Swatch active state */
    '.sb-color-swatch.active{box-shadow:0 0 0 3px #fff,0 0 0 5px ' + c.gl + '!important}',
    /* — Light theme overrides (gold text on white must use darker shade) — */
    '[data-theme="light"] .fcol-title{color:' + lightGold + '!important}',
    '[data-theme="light"] .f-acct-label{color:' + lightGold + '!important}',
    '[data-theme="light"] .f-contact-list li a{color:' + lightGold + '!important}',
    '[data-theme="light"] .fcol ul li a .fla{color:' + lightGold + '!important}',
    '[data-theme="light"] .flinks a:hover{color:' + lightGold + '!important}',
    '[data-theme="light"] .fcol ul li a:hover{color:' + c.gl + '!important}',
  ].join('\n');

  /* Update active swatch UI */
  document.querySelectorAll('.sb-color-swatch').forEach(function(sw) {
    sw.classList.toggle('active', sw.dataset.color === colorId);
  });
}

/* ── Font picker ─────────────────────────────────────────────── */
var FONTS = [
  /* ── Single fonts ── */
  { id:'nunito',  label:'Nunito',  sub:'Classic default', combo:false,
    h:"'Nunito',sans-serif", s:"'Nunito',sans-serif", b:"'Nunito',sans-serif",
    google:[] },
  { id:'poppins', label:'Poppins', sub:'Clean & modern',  combo:false,
    h:"'Poppins',sans-serif", s:"'Poppins',sans-serif", b:"'Poppins',sans-serif",
    google:['Poppins:wght@300;400;500;600;700;800'] },
  { id:'inter',   label:'Inter',   sub:'Sharp & readable', combo:false,
    h:"'Inter',sans-serif", s:"'Inter',sans-serif", b:"'Inter',sans-serif",
    google:['Inter:wght@300;400;500;600;700;800'] },
  /* ── Combos ── */
  { id:'cool',    label:'Cool',    sub:'Playfair · Poppins', combo:true,
    h:"'Playfair Display',serif",
    s:"'Nunito',sans-serif",
    b:"'Poppins',sans-serif",
    google:['Playfair+Display:wght@400;500;600;700;800;900','Poppins:wght@300;400;500;600;700;800'] },
  { id:'decent',  label:'Decent',  sub:'Nunito · Inter', combo:true,
    h:"'Nunito',sans-serif",
    s:"'Inter',sans-serif",
    b:"'Inter',sans-serif",
    google:['Inter:wght@300;400;500;600;700;800'] },
  { id:'awesome', label:'Awesome', sub:'Playfair · Josefin', combo:true,
    h:"'Playfair Display',serif",
    s:"'Josefin Sans',sans-serif",
    b:"'Josefin Sans',sans-serif",
    google:['Playfair+Display:wght@400;500;600;700;800;900','Josefin+Sans:wght@300;400;500;600;700'] },
  { id:'super',   label:'Super',   sub:'Caudex · Museo', combo:true,
    h:"'Caudex',serif",
    s:"'Museo Moderno',sans-serif",
    b:"-apple-system,BlinkMacSystemFont,'Avenir Next',Avenir,'Helvetica Neue',sans-serif",
    google:['Caudex:wght@400;700','Museo+Moderno:wght@300;400;500;600;700;800;900'] }
];

var currentFont = localStorage.getItem('ag_font') || 'decent';

function applyFont(fontId) {
  var f = FONTS.find(function(x){ return x.id === fontId; });
  if (!f) return;
  currentFont = fontId;
  localStorage.setItem('ag_font', fontId);

  /* Load Google Fonts if needed */
  f.google.forEach(function(g) {
    var fid = 'ag-gf-' + g.split(':')[0].toLowerCase().replace(/\+/g,'-');
    if (!document.getElementById(fid)) {
      var lnk = document.createElement('link');
      lnk.id   = fid;
      lnk.rel  = 'stylesheet';
      lnk.href = 'https://fonts.googleapis.com/css2?family=' + g + '&display=swap';
      document.head.appendChild(lnk);
    }
  });

  /* Inject CSS */
  var styleId = 'ag-font-override';
  var el = document.getElementById(styleId);
  if (!el) { el = document.createElement('style'); el.id = styleId; document.head.appendChild(el); }

  if (f.combo) {
    /* Combo: headings / sub-headings / body get different fonts */
    el.textContent = [
      '*, body, #nav, #navbar, footer { font-family: ' + f.b + ' !important; }',
      'h4,h5,h6,.stag,.section-tag,.ap-city,.td-city,.mb-feat-city,.fcol-title,.f-acct-label,.pbl,.sb-label { font-family: ' + f.s + ' !important; }',
      'h1,h2,h3,.page-hero h1,.ph-inner h1,.ap-hero h1,.intl-hero h1,.post-title,.hero-title,.ftab { font-family: ' + f.h + ' !important; }'
    ].join('\n');
  } else {
    el.textContent = '*, body, #nav, #navbar, footer { font-family: ' + f.b + ' !important; }';
  }

  /* Update button states */
  document.querySelectorAll('.sb-font-btn').forEach(function(btn) {
    btn.classList.toggle('active', btn.dataset.font === fontId);
  });
}

/* ── Generate booking reference ─────────────────────────────── */
function generateBookingRef() {
  var year = new Date().getFullYear();
  var key  = 'ag_ref_' + year;
  var seq  = parseInt(localStorage.getItem(key) || '0') + 1;
  localStorage.setItem(key, seq);
  return 'TB-' + year + '-' + String(seq).padStart(4, '0');
}

/* ── Policy page tab switcher ───────────────────────────────── */
function showSection(id, el) {
  document.querySelectorAll('.policy-section').forEach(function (s) {
    s.classList.remove('active');
  });
  document.querySelectorAll('.policy-nav a').forEach(function (a) {
    a.classList.remove('active');
  });
  var sec = document.getElementById('sec-' + id);
  if (sec) sec.classList.add('active');
  if (el)  el.classList.add('active');
}

/* ── Custom cursor ──────────────────────────────────────────── */
function initCursor() {
  var c = document.getElementById('cursor');
  var r = document.getElementById('cursor-ring');
  if (c && r) {
    document.addEventListener('mousemove', function (e) {
      c.style.left = e.clientX + 'px'; c.style.top = e.clientY + 'px';
      r.style.left = e.clientX + 'px'; r.style.top = e.clientY + 'px';
    });
  }
}

/* ── Navbar scroll effect ───────────────────────────────────── */
function initNavScroll() {
  var nb = document.getElementById('nav') || document.getElementById('navbar');
  if (!nb) return;
  window.addEventListener('scroll', function () {
    nb.classList.toggle('scrolled', window.scrollY > 30);
  });
}

/* ── Mobile menu ────────────────────────────────────────────── */
function toggleMenu() {
  var m = document.getElementById('mob') || document.getElementById('mobile-menu');
  if (m) m.classList.toggle('open');
}
function closeMenu() {
  var m = document.getElementById('mob') || document.getElementById('mobile-menu');
  if (m) m.classList.remove('open');
}

/* ── Set minimum dates on date inputs ───────────────────────── */
function setMinDates() {
  var today = new Date().toISOString().split('T')[0];
  document.querySelectorAll('input[type="date"]').forEach(function (el) {
    if (!el.min) el.min = today;
  });
}

/* ── Inject sidebar (theme + font) + WhatsApp button ────────── */
function injectSidebar() {
  var themeLabel = currentTheme === 'dark' ? '\u2600 Light Mode' : '\uD83C\uDF19 Dark Mode';
  var themeTitle = currentTheme === 'dark' ? 'Switch to Light theme' : 'Switch to Dark theme';

  /* Build font buttons — singles first, then a divider, then combos */
  var singleFonts = FONTS.filter(function(f){ return !f.combo; });
  var comboFonts  = FONTS.filter(function(f){ return  f.combo; });
  function makeFontBtn(f) {
    var active = f.id === currentFont ? ' active' : '';
    return '<button class="sb-font-btn' + active + '" data-font="' + f.id + '" ' +
           'onclick="applyFont(\'' + f.id + '\')" title="' + f.label + '">' +
           '<span class="sb-font-name">' + f.label + '</span>' +
           '<span class="sb-font-sub">' + f.sub + '</span>' +
           '</button>';
  }
  var fontBtnsHtml =
    singleFonts.map(makeFontBtn).join('') +
    '<div class="sb-font-div">Combos</div>' +
    comboFonts.map(makeFontBtn).join('');

  /* Build colour swatches */
  var colorSwatchesHtml = Object.keys(COLOR_THEMES).map(function(id) {
    var c = COLOR_THEMES[id];
    var active = id === currentColor ? ' active' : '';
    return '<button class="sb-color-swatch' + active + '" data-color="' + id + '" ' +
           'style="background:' + c.gl + '" onclick="applyColor(\'' + id + '\')" ' +
           'title="' + c.label + '"></button>';
  }).join('');

  var sid = document.createElement('div');
  sid.id  = 'ag-sidebar';
  sid.innerHTML =
    '<button id="sidebar-toggle" onclick="toggleSidebar()" aria-label="Settings">' +
    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
    '<circle cx="12" cy="12" r="3"/>' +
    '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>' +
    '</svg>' +
    '</button>' +
    '<div id="sidebar-panel">' +
      '<div class="sb-section">' +
        '<div class="sb-label">Theme</div>' +
        '<button id="theme-toggle-btn" onclick="toggleTheme()" class="sb-theme-btn" title="' + themeTitle + '">' + themeLabel + '</button>' +
        '<div class="sb-hint">Saved across all pages</div>' +
      '</div>' +
      '<div class="sb-section">' +
        '<div class="sb-label">Accent Colour</div>' +
        '<div class="sb-color-row">' + colorSwatchesHtml + '</div>' +
        '<div class="sb-hint">Changes all gold accents</div>' +
      '</div>' +
      '<div class="sb-section">' +
        '<div class="sb-label">Font</div>' +
        '<div class="sb-font-row">' + fontBtnsHtml + '</div>' +
        '<div class="sb-hint">Applies site-wide</div>' +
      '</div>' +
    '</div>';

  document.body.appendChild(sid);

  /* WhatsApp button */
  var wa = document.createElement('a');
  wa.id  = 'wa-btn';
  wa.href = 'https://wa.me/919536896071?text=Hi%2C%20I%20need%20help%20with%20airport%20meet%20%26%20greet%20booking';
  wa.target = '_blank';
  wa.rel    = 'noopener noreferrer';
  wa.setAttribute('aria-label', 'Chat on WhatsApp');
  wa.innerHTML =
    '<svg viewBox="0 0 24 24" fill="currentColor">' +
    '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>' +
    '</svg>' +
    '<span>Chat Now</span>';
  document.body.appendChild(wa);

  /* ── Sidebar & WhatsApp styles ── */
  var style = document.createElement('style');
  style.textContent = [
    '#ag-sidebar{position:fixed;right:0;top:50%;transform:translateY(-50%);z-index:9000;display:flex;align-items:center;flex-direction:row}',
    '#sidebar-toggle{width:44px;height:44px;background:var(--gl);border:none;border-radius:8px 0 0 8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:-3px 0 16px rgba(0,0,0,.35);transition:.3s;flex-shrink:0}',
    '#sidebar-toggle:hover{width:50px}',
    '#sidebar-panel{background:var(--sb-bg,rgba(8,12,16,.97));border:1px solid var(--br);border-right:none;border-radius:12px 0 0 12px;padding:1.25rem 1rem;display:none;box-shadow:-8px 0 40px rgba(0,0,0,.5);min-width:210px;max-height:90vh;overflow-y:auto;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px)}',
    '#sidebar-panel.open{display:block}',
    '.sb-label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--sl);margin-bottom:.5rem}',
    '.sb-section{margin-bottom:1.1rem}.sb-section:last-child{margin-bottom:0}',
    '.sb-hint{font-size:.58rem;color:var(--sl);margin-top:.35rem;line-height:1.4;opacity:.75}',
    '.sb-theme-btn{width:100%;background:var(--gl);border:none;border-radius:9px;color:#fff;padding:.6rem .85rem;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;transition:.25s;letter-spacing:.03em;display:flex;align-items:center;justify-content:center;gap:6px}',
    '.sb-theme-btn:hover{opacity:.88;transform:translateY(-1px)}',
    /* Font picker row */
    '.sb-font-row{display:flex;flex-direction:column;gap:5px}',
    '.sb-font-div{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--sl);opacity:.6;margin:.4rem 0 .2rem;padding-left:2px}',
    '.sb-font-btn{width:100%;background:rgba(255,255,255,.06);border:1px solid var(--br);border-radius:8px;color:var(--sl);padding:.45rem .75rem;font-size:.8rem;font-weight:600;cursor:pointer;font-family:inherit;transition:.2s;text-align:left;display:flex;align-items:center;justify-content:space-between;gap:6px}',
    '.sb-font-name{font-weight:700;font-size:.8rem}',
    '.sb-font-sub{font-size:.62rem;font-weight:400;opacity:.65;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px}',
    '.sb-font-btn:hover{background:rgba(212,175,55,.12);color:var(--gl)}',
    '.sb-font-btn.active{background:rgba(212,175,55,.18);border-color:var(--gl);color:var(--gl)}',
    /* WhatsApp */
    '#wa-btn{position:fixed;bottom:2rem;right:1.5rem;z-index:8999;display:flex;align-items:center;gap:8px;background:#25D366;color:#fff;padding:.75rem 1.1rem;border-radius:50px;text-decoration:none;font-size:.85rem;font-weight:700;font-family:inherit;box-shadow:0 6px 24px rgba(37,211,102,.45);transition:.3s;white-space:nowrap}',
    '#wa-btn:hover{background:#1ebe5d;transform:translateY(-3px)}',
    '#wa-btn svg{width:20px;height:20px;flex-shrink:0}',
    '@media(max-width:600px){#wa-btn span{display:none}#wa-btn{padding:.75rem}#wa-btn svg{width:24px;height:24px}}',
    /* Colour swatches */
    '.sb-color-row{display:flex;flex-wrap:wrap;gap:7px;margin-top:2px}',
    '.sb-color-swatch{width:28px;height:28px;border-radius:50%;border:2px solid rgba(255,255,255,.25);cursor:pointer;transition:transform .2s,box-shadow .2s;flex-shrink:0;padding:0}',
    '.sb-color-swatch:hover{transform:scale(1.18)}',
    '.sb-color-swatch.active{box-shadow:0 0 0 3px #fff,0 0 0 5px var(--gl);transform:scale(1.12)}',
    /* Light theme panel overrides — full black text */
    '[data-theme="light"] #sidebar-panel{background:rgba(255,255,255,.98) !important;border-color:rgba(0,0,0,.12);box-shadow:-8px 0 32px rgba(0,0,0,.14)}',
    '[data-theme="light"] .sb-label{color:#000000 !important}',
    '[data-theme="light"] .sb-hint{color:#000000 !important;opacity:.55}',
    '[data-theme="light"] .sb-font-div{color:#000000 !important}',
    '[data-theme="light"] .sb-font-btn{background:#f3f4f6;border-color:rgba(0,0,0,.14);color:#000000 !important}',
    '[data-theme="light"] .sb-font-name{color:#000000 !important}',
    '[data-theme="light"] .sb-font-sub{color:#000000 !important}',
    '[data-theme="light"] .sb-font-btn:hover{background:rgba(212,175,55,.1);color:var(--gl) !important}',
    '[data-theme="light"] .sb-font-btn:hover .sb-font-name{color:var(--gl) !important}',
    '[data-theme="light"] .sb-font-btn:hover .sb-font-sub{color:var(--gl) !important}',
    '[data-theme="light"] .sb-font-btn.active{background:rgba(212,175,55,.15);border-color:var(--gl);color:var(--gl) !important}',
    '[data-theme="light"] .sb-font-btn.active .sb-font-name{color:var(--gl) !important}',
    '[data-theme="light"] .sb-font-btn.active .sb-font-sub{color:var(--gl) !important}',
    '[data-theme="light"] .sb-color-swatch.active{box-shadow:0 0 0 3px #fff,0 0 0 5px var(--gl)}'
  ].join('\n');
  document.head.appendChild(style);

  /* Apply saved theme, colour and font — always run font + colour so defaults work for new users */
  applyTheme(currentTheme);
  applyColor(currentColor);
  applyFont(currentFont);
}

function toggleSidebar() {
  var p = document.getElementById('sidebar-panel');
  if (p) p.classList.toggle('open');
}

/* ── DOMContentLoaded init ──────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  initCursor();
  initNavScroll();
  injectSidebar();
  setMinDates();
});
