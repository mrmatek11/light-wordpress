// Vanilla JS, no build. Same-origin with ClassicPress: cookie login + REST nonce.
const api = '/wp-json/wp/v2/';
const txt = h => new DOMParser().parseFromString(h, 'text/html').body.textContent;
const $ = id => document.getElementById(id);
const el = (tag, cls, text) => Object.assign(document.createElement(tag), { className: cls || '', textContent: text ?? '' });

async function get(path) {
  const r = await fetch(api + path, { headers: { 'X-WP-Nonce': nonce }, credentials: 'same-origin' });
  if (!r.ok) throw new Error(r.status);
  return { data: await r.json(), total: +r.headers.get('X-WP-Total') };
}

const nonce = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' })
  .then(r => (r.ok ? r.text() : Promise.reject()))
  .catch(() => { location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(location.pathname); return ''; });

const counts = [['Wpisy', 'posts?status=publish'], ['Szkice', 'posts?status=draft'], ['Strony', 'pages?status=publish'], ['Komentarze', 'comments'], ['Użytkownicy', 'users']];
const stats = $('stats');
counts.forEach(async ([label, q]) => {
  const s = el('div', 'stat'); s.append(el('span', '', label), el('b', '', '…')); stats.append(s);
  try { s.lastChild.textContent = (await get(q + (q.includes('?') ? '&' : '?') + 'per_page=1&_fields=id')).total; }
  catch { s.lastChild.textContent = '–'; }
});

const day = d => new Date(d).toLocaleDateString('pl');
get('posts?per_page=6&status=any&_fields=id,title,status,date').then(({ data }) =>
  data.forEach(p => {
    const a = el('a'); a.href = `/wp-admin/post.php?post=${p.id}&action=edit`;
    a.append(el('span', '', txt(p.title.rendered) || '(bez tytułu)'), el('em', '', `${p.status} · ${day(p.date)}`));
    $('posts').append(a);
  })).catch(() => $('posts').append(el('em', '', 'Błąd ładowania')));

get('comments?per_page=6&_fields=id,author_name,content,date').then(({ data }) =>
  data.forEach(c => {
    const d = el('div');
    d.append(el('span', '', `${c.author_name}: ${txt(c.content.rendered).slice(0, 70)}`), el('em', '', day(c.date)));
    $('comments').append(d);
  })).catch(() => $('comments').append(el('em', '', 'Błąd ładowania')));

get('../').then(({ data }) => { $('site').textContent = data.name; document.title = data.name; }).catch(() => {});
