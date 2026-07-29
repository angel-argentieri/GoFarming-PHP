const BASE = '/gofarming/PUBLIC';

async function post(rota, dados) {
    const res = await fetch(BASE + '/' + rota, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(dados)
    });
    return res.json();
}

async function get(rota) {
    const res = await fetch(BASE + '/' + rota, {
        credentials: 'include'
    });
    return res.json();
}

async function del(rota, dados) {
    const res = await fetch(BASE + '/' + rota, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(dados)
    });
    return res.json();
}

function toast(msg) {
    let t = document.getElementById('toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        t.className = 'toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('visivel');
    setTimeout(() => t.classList.remove('visivel'), 2500);
}
