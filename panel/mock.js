// Fake REST responses for offline preview: open index.html?demo
const d = n => new Date(Date.now() - n * 864e5).toISOString();
const posts = [['Jak zacząć z ClassicPress', 'publish'], ['Nowy panel CMS — pierwsze wrażenia', 'draft'], ['Optymalizacja obrazów w WWW', 'publish'], ['Cennik 2026', 'pending'], ['Poradnik: motywy potomne', 'publish'], ['Hello world', 'publish']]
  .map(([t, status], i) => ({ id: i + 1, status, date: d(i), title: { rendered: t } }));
const comments = [['Anna', 'Świetny artykuł, dzięki za konkretne przykłady!'], ['Marek', 'Czy to działa też z WooCommerce?'], ['Ola', 'Czekam na kolejną część.'], ['Piotr', 'Super, wdrożę u siebie.']]
  .map(([a, c], i) => ({ id: i, author_name: a, date: d(i), content: { rendered: `<p>${c}</p>` } }));
const totals = { 'posts?status=publish': 42, 'posts?status=draft': 7, 'pages?status=publish': 12, comments: 128, users: 5 };
export default path => {
  const key = Object.keys(totals).find(k => path.startsWith(k));
  const data = path.startsWith('posts?per') ? posts : path.startsWith('comments?per') ? comments : path === '../' ? { name: 'Moja strona' } : [];
  return { data, total: totals[key] ?? 0 };
};
