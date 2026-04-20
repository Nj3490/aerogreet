/**
 * nav-shared.js — Shared header/footer injector for Aero Greet India
 * Include this script in every page. It will replace elements with
 * id="site-header" and id="site-footer" with the standard markup.
 */
(function(){
  var currentPage = (function(){
    var p = window.location.pathname.split('/').pop();
    if(!p || p === 'index.html' || p === '') return 'home';
    if(p === 'book-now.html') return 'book-now';
    if(p === 'airports.html') return 'airports';
    if(p === 'international.html') return 'international';
    if(p === 'about.html') return 'about';
    if(p === 'contact.html') return 'contact';
    if(p === 'blog.html') return 'blog';
    return '';
  })();

  // Determine root path
  var root = (function(){
    var p = window.location.pathname;
    if(p.includes('/dashboard/') || p.includes('/admin/')) return '../';
    return '';
  })();
  var homeHref = root || '/';
  var bookNowHref = root + 'book-now.html';

  var navHtml = `
<nav id="nav">
  <a href="${homeHref}" class="logo">
    <div class="logo-icon">&#9992;</div>
    <div class="logo-text">Aero Greet India<small>Premium Airport Services</small></div>
  </a>
  <ul class="nav-ul">
    <li><a href="${homeHref}" ${currentPage==='home'?'class="nav-active"':''}>Home</a></li>
    <li><a href="${root}airports.html" ${currentPage==='airports'?'class="nav-active"':''}>Airports</a></li>
    <li><a href="${root}international.html" ${currentPage==='international'?'class="nav-active"':''}>International</a></li>
    <li><a href="${root}about.html" ${currentPage==='about'?'class="nav-active"':''}>About Us</a></li>
    <li><a href="${root}contact.html" ${currentPage==='contact'?'class="nav-active"':''}>Contact</a></li>
    <li><a href="${bookNowHref}" class="nav-book${currentPage==='book-now'?' nav-active':''}">Book Now</a></li>
  </ul>
  <div class="nav-right" style="display:flex;align-items:center;gap:8px">
    <a href="${root}dashboard/" class="nav-login-btn" id="nav-login-btn" title="Login / Track Booking" style="display:flex;align-items:center;gap:6px;background:rgba(212,175,55,.12);border:1px solid rgba(212,175,55,.3);color:#D4AF37;padding:7px 14px;border-radius:8px;font-size:.8rem;font-weight:600;text-decoration:none;transition:.2s">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      <span class="login-label">Login</span>
    </a>
  </div>
  <button class="hb" onclick="toggleMenu()" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>
<div class="mob" id="mob">
  <a href="${root}dashboard/" onclick="closeMenu()" style="display:flex;align-items:center;gap:8px;color:#D4AF37;font-weight:600">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
    Login / Track Booking
  </a>
  <a href="${homeHref}" onclick="closeMenu()">Home</a>
  <a href="${root}airports.html" onclick="closeMenu()">Airports</a>
  <a href="${root}international.html" onclick="closeMenu()">International</a>
  <a href="${root}about.html" onclick="closeMenu()">About Us</a>
  <a href="${root}contact.html" onclick="closeMenu()">Contact</a>
  <a href="${root}privacy.html" onclick="closeMenu()">Privacy Policy</a>
  <a href="${bookNowHref}" onclick="closeMenu()" class="mob-book">&#9992; Book Now</a>
</div>`;

  var footerHtml = `
<footer class="footer" id="footer">
  <div class="fin">
    <div class="fgrid">
      <div class="fcol">
        <div class="fbrand">&#9992; Aero Greet India</div>
        <p class="ftagline">Premium airport meet &amp; greet services across 90+ Indian airports. A brand of <a href="https://www.travelblooper.com" target="_blank" rel="noopener noreferrer" style="color:var(--gl);text-decoration:none">Travel Blooper</a>.</p>
        <div class="fsocials" style="display:flex;gap:10px;margin-top:1rem">
          <a href="https://wa.me/919536896071" target="_blank" rel="noopener noreferrer" style="color:var(--sl);font-size:.82rem;text-decoration:none">&#128172; WhatsApp</a>
        </div>
      </div>
      <div class="fcol">
        <div class="fcol-title">Services</div>
        <ul>
          <li><a href="${root}airports.html">Arrival Meet &amp; Greet</a></li>
          <li><a href="${root}airports.html">Departure Assistance</a></li>
          <li><a href="${root}airports.html">Transit Meet &amp; Greet</a></li>
          <li><a href="${root}airports.html">Senior &amp; Special Care</a></li>
          <li><a href="${root}airports.html">Porter Services</a></li>
        </ul>
      </div>
      <div class="fcol">
        <div class="fcol-title">Quick Links</div>
        <ul>
          <li><a href="${root}airports.html">All Airports</a></li>
          <li><a href="${root}international.html">International</a></li>
          <li><a href="${root}about.html">About Us</a></li>
          <li><a href="${root}blog.html">Blog</a></li>
          <li><a href="${root}contact.html">Contact Us</a></li>
          <li><a href="${root}dashboard/" style="color:#D4AF37">&#128203; Login / Track Booking</a></li>
        </ul>
      </div>
      <div class="fcol">
        <div class="fcol-title">Contact</div>
        <ul>
          <li><a href="mailto:sales@aerogreetindia.com">sales@aerogreetindia.com</a></li>
          <li><a href="tel:+919536896071">+91 95368 96071</a></li>
          <li><a href="https://wa.me/919536896071" target="_blank" rel="noopener noreferrer">WhatsApp Us</a></li>
        </ul>
      </div>
    </div>
    <div class="fbot">
      <div style="font-size:.75rem;color:rgba(238,242,247,.4)">&copy; 2025 Aero Greet India &mdash; A brand of Travel Blooper. All rights reserved.</div>
      <div class="flinks">
        <a href="${root}privacy.html">Privacy Policy</a>
        <a href="${root}terms.html">Terms &amp; Conditions</a>
        <a href="${root}refund.html">Refund Policy</a>
      </div>
    </div>
  </div>
</footer>`;

  document.addEventListener('DOMContentLoaded', function(){
    var headerEl = document.getElementById('site-header');
    if(headerEl) headerEl.outerHTML = navHtml;
    var footerEl = document.getElementById('site-footer');
    if(footerEl) footerEl.outerHTML = footerHtml;
  });
})();
