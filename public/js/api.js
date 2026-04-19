// ── Helpers only — API integration will be added in Step 11 ──

function statusBadge(s) {
  const labels = { pending:'Pending', assigned:'Assigned', picked_up:'Picked Up', on_the_way:'On The Way', delivered:'Delivered', failed:'Failed' };
  return `<span class="badge-status badge-${s}">${labels[s] || s}</span>`;
}

function timeAgo(d) {
  const m = Math.floor((Date.now() - new Date(d)) / 60000);
  if (m < 1) return 'just now';
  if (m < 60) return `${m} minutes ago`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h} hours ago`;
  return `${Math.floor(h/24)} days ago`;
}

function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' });
}

function fmtMoney(n) { return parseFloat(n || 0).toLocaleString('en-US') + ' IQD'; }

function formatIraqiPhone(input) {
  let value = input.value.replace(/\D/g, '').slice(0, 11);
  if (value.length > 4) value = value.slice(0, 4) + ' ' + value.slice(4);
  if (value.length > 8) value = value.slice(0, 8) + ' ' + value.slice(8);
  input.value = value;
}

function validateIraqiPhone(phone) {
  const cleaned = phone.replace(/\s/g, '');
  return /^07\d{9}$/.test(cleaned);
}

function toast(msg, type = 'success') {
  const colors = { success: '#dcfce7', error: '#fee2e2', info: '#dbeafe' };
  const textColors = { success: '#166534', error: '#991b1b', info: '#1e40af' };
  const icons = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
  const el = document.createElement('div');
  el.style.cssText = `position:fixed;top:1rem;right:1rem;z-index:9999;background:${colors[type]};color:${textColors[type]};border-radius:10px;padding:.75rem 1.25rem;font-size:.875rem;font-weight:500;box-shadow:0 4px 15px rgba(0,0,0,.1);display:flex;align-items:center;gap:.5rem;max-width:320px;font-family:'Plus Jakarta Sans',sans-serif`;
  el.innerHTML = `<i class="bi bi-${icons[type]}"></i>${msg}`;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}
