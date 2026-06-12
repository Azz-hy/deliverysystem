const BASE = window.LDMS_API_URL;

// ── Auth ────────────────────────────────────────────────────────────────────
const Auth = {
  setAuth(token, user) {
    localStorage.setItem('ldms_token', token);
    localStorage.setItem('ldms_user', JSON.stringify(user));
  },
  getToken() { return localStorage.getItem('ldms_token'); },
  getUser()  { const u = localStorage.getItem('ldms_user'); return u ? JSON.parse(u) : null; },
  isLogged() { return !!this.getToken(); },
  clear()    { localStorage.removeItem('ldms_token'); localStorage.removeItem('ldms_user'); },
};

function requireAuth(role) {
  const user = Auth.getUser();
  const token = Auth.getToken();
  if (!token || !user) {
    window.location.href = '../index.html';
    return null;
  }
  if (role && user.role !== role) {
    window.location.href = '../index.html';
    return null;
  }
  return user;
}

// ── HTTP helpers ─────────────────────────────────────────────────────────────
async function _request(method, path, body) {
  const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
  const token = Auth.getToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(BASE + path, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const data = await res.json().catch(() => ({}));
  if (res.status === 401) { Auth.clear(); window.location.href = '../index.html'; return; }
  if (!res.ok) { const err = new Error(data.message || `HTTP ${res.status}`); err.errors = data.errors; throw err; }
  return data;
}

function GET(path)        { return _request('GET',    path); }
function POST(path, body) { return _request('POST',   path, body); }
function PUT(path, body)  { return _request('PUT',    path, body); }
function DELETE(path)     { return _request('DELETE', path); }

// ── UI helpers ────────────────────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const el = document.createElement('div');
  const bg = type === 'success' ? '#059669' : '#dc2626';
  el.style.cssText = `position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;background:${bg};color:#fff;padding:.75rem 1.25rem;border-radius:10px;font-size:.875rem;font-weight:600;box-shadow:0 4px 15px rgba(0,0,0,.25);animation:fadeIn .2s ease`;
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3000);
}

function statusBadge(status) {
  const map = {
    pending:    ['Pending',    '#fef3c7','#92400e'],
    assigned:   ['Assigned',   '#dbeafe','#1e40af'],
    picked_up:  ['Picked Up',  '#ede9fe','#5b21b6'],
    on_the_way: ['On The Way', '#e0f2fe','#075985'],
    delivered:  ['Delivered',  '#dcfce7','#166534'],
    failed:     ['Failed',     '#fee2e2','#991b1b'],
  };
  const [label, bg, color] = map[status] || [status, '#f1f5f9', '#475569'];
  return `<span style="background:${bg};color:${color};font-size:.72rem;font-weight:600;padding:.3rem .7rem;border-radius:20px;white-space:nowrap">${label}</span>`;
}

function fmtMoney(val) {
  if (val == null) return '—';
  return '$' + parseFloat(val).toFixed(2);
}

function fmtDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
}

function timeAgo(str) {
  if (!str) return '—';
  const diff = Date.now() - new Date(str).getTime();
  const m = Math.floor(diff / 60000);
  if (m < 1)  return 'just now';
  if (m < 60) return `${m}m ago`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h}h ago`;
  return `${Math.floor(h / 24)}d ago`;
}

function formatIraqiPhone(input) {
  let v = input.value.replace(/\D/g, '');
  if (v.length > 4) v = v.slice(0,4) + ' ' + v.slice(4);
  if (v.length > 8) v = v.slice(0,8) + ' ' + v.slice(8);
  input.value = v.slice(0, 14);
}

function validateIraqiPhone(phone) {
  return /^07[3-9]\d[\s]?\d{3}[\s]?\d{4}$/.test(phone.replace(/\s/g,'')) === false
    ? /^07[3-9]\d{8}$/.test(phone.replace(/\s/g,''))
    : true;
}
