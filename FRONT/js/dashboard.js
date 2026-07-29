document.getElementById('saudacao').textContent = 'Olá, ' + (localStorage.getItem('nome') || 'Jardineiro');

async function carregarPlantas() {
    const plantas = await get('plantas');
    const lista = document.getElementById('lista');

    if (!plantas.length) {
        lista.innerHTML = `
            <div style="text-align:center;padding:60px 0;color:var(--text-muted);">
                <div style="font-size:48px;margin-bottom:12px;">🌿</div>
                <p>Seu jardim está vazio.</p>
                <p style="margin-top:8px;font-size:13px;">Toque em 📷 para escanear uma planta.</p>
            </div>
        `;
        return;
    }

    lista.innerHTML = plantas.map(p => {
        const pendente = p.rega_hoje === 'pendente';
        return `
            <div class="card" onclick="window.location.href='planta.html?id=${p.id}'" style="cursor:pointer;">
                <div class="row">
                    <img src="${p.foto_url || 'css/placeholder.png'}" 
                         style="width:64px;height:64px;border-radius:12px;object-fit:cover;border:1.5px solid var(--border);flex-shrink:0;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.nome}</div>
                        <div class="muted" style="font-size:12px;margin:2px 0 8px;">${p.especie || ''}</div>
                        <span class="badge ${pendente ? 'badge-pendente' : 'badge-ok'}">
                            ${pendente ? '💧 Regar hoje' : '✓ Em dia'}
                        </span>
                    </div>
                    <svg width="20" height="20" fill="none" stroke="var(--text-muted)" stroke-width="1.8" viewBox="0 0 24 24">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </div>
            </div>
        `;
    }).join('');
}

carregarPlantas();
