// ============================================================
// script.js
// Simulador Interactivo de Hamburguesa Personalizada Mostaza
// ============================================================

const INGREDIENTES = {
  pan:     { precio: 0,    label: 'Pan Tostado Mostaza', emoji: '🍞', fijo: true },
  carne:   { precio: 2500, label: 'Carne a la Parrilla', emoji: '🥩' },
  pepino:  { precio: 1500, label: 'Pepinillos',          emoji: '🥒' },
  huevo:   { precio: 500,  label: 'Huevo a la Plancha',  emoji: '🍳' },
  queso:   { precio: 1000, label: 'Queso Cheddar',       emoji: '🧀' },
  lechuga: { precio: 500,  label: 'Lechuga Fresca',      emoji: '🥬' },
  tomate:  { precio: 600,  label: 'Tomate',              emoji: '🍅' },
  tocino:  { precio: 1000, label: 'Bacon / Tocino',      emoji: '🥓' },
  cebolla: { precio: 900,  label: 'Cebolla Morada',      emoji: '🧅' },
};

const ADEREZOS = {
  mayonesa:       { precio: 0,   label: 'Mayonesa Clásica',      emoji: '🥫' },
  ketchup:        { precio: 0,   label: 'Ketchup Mostaza',       emoji: '🍅' },
  mostaza:        { precio: 0,   label: 'Mostaza Suave & Dulce', emoji: '🟡' },
  barbacoa:       { precio: 300, label: 'Barbacoa BBQ',          emoji: '🍯' },
  cheddar:        { precio: 450, label: 'Salsa Cheddar Fundido', emoji: '🧀' },
  salsa_especial: { precio: 350, label: 'Salsa Especial Mega',   emoji: '⭐' },
};

const ACOMPAÑAMIENTOS = {
  papas:   { precio: 1500, label: 'Papas Fritas',     emoji: '🍟' },
  nuggets: { precio: 1800, label: 'Nuggets de Pollo', emoji: '🍗' },
};

const estado = {
  ingredientes: { pan: true, carne: true, queso: true, lechuga: true, tomate: true, pepino: true, huevo: true, tocino: true, cebolla: true },
  aderezos: { mayonesa: true, ketchup: true, mostaza: true, barbacoa: false, cheddar: false, salsa_especial: false },
  acompanamiento: 'papas',
};

const resumenTexto  = document.getElementById('resumenTexto');
const precioTotal   = document.getElementById('precioTotal');
const acompBubble   = document.getElementById('acompBubble');
const acompEmoji    = document.getElementById('acompEmoji');
const btnAgregar    = document.getElementById('btnAgregar');

function toggleCapa(layer, visible) {
  // El pan nunca se puede ocultar
  if (layer === 'pan') {
    visible = true;
  }
  const capas = layer === 'pan'
    ? [document.getElementById('layer-pan'), document.getElementById('layer-pan-bottom')]
    : [document.getElementById(`layer-${layer}`)];

  capas.forEach(el => {
    if (!el) return;
    if (visible) {
      el.classList.remove('hidden');
    } else {
      el.classList.add('hidden');
    }
  });
}

function actualizarResumen() {
  // Garantizar pan siempre activo
  estado.ingredientes.pan = true;

  const activosIng = Object.entries(estado.ingredientes)
    .filter(([, v]) => v)
    .map(([k]) => `${INGREDIENTES[k].emoji} ${INGREDIENTES[k].label}`);

  const activosAde = Object.entries(estado.aderezos)
    .filter(([, v]) => v)
    .map(([k]) => `${ADEREZOS[k].emoji} ${ADEREZOS[k].label}`);

  const acomp = ACOMPAÑAMIENTOS[estado.acompanamiento];

  let html = `<strong>${activosIng.join(' · ')}</strong>`;
  if (activosAde.length > 0) {
    html += `<br><span style="color:#B45309;font-size:0.82rem;font-weight:700;">Salsas: ${activosAde.join(', ')}</span>`;
  }
  html += `<br><span style="color:#15803D;font-size:0.85rem;font-weight:700;">+ ${acomp.emoji} ${acomp.label}</span>`;
  resumenTexto.innerHTML = html;

  const totalIng = Object.entries(estado.ingredientes)
    .filter(([, v]) => v)
    .reduce((sum, [k]) => sum + INGREDIENTES[k].precio, 0);

  const totalAde = Object.entries(estado.aderezos)
    .filter(([, v]) => v)
    .reduce((sum, [k]) => sum + ADEREZOS[k].precio, 0);

  const total = totalIng + totalAde + acomp.precio;
  precioTotal.textContent = total.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function actualizarBurbuja(tipo) {
  const acomp = ACOMPAÑAMIENTOS[tipo];
  if (acomp && acompEmoji && acompBubble) {
    acompEmoji.textContent = acomp.emoji;
    acompBubble.classList.remove('visible');
    setTimeout(() => acompBubble.classList.add('visible'), 40);
  }
}

function actualizarTarjetasAcomp(valorSeleccionado) {
  document.querySelectorAll('.acomp-card').forEach(card => {
    const radio = card.querySelector('input[type="radio"]');
    if (radio && radio.value === valorSeleccionado) {
      card.classList.add('activa');
    } else {
      card.classList.remove('activa');
    }
  });
}

// Event listeners para ingredientes
document.querySelectorAll('.ingredient-list input[type="checkbox"]').forEach(checkbox => {
  checkbox.addEventListener('change', (e) => {
    const layer = e.target.dataset.layer;
    if (!layer) return;

    // Bloqueo explícito del Pan: no se puede desmarcar
    if (layer === 'pan') {
      e.target.checked = true;
      estado.ingredientes.pan = true;
      toggleCapa('pan', true);
      return;
    }

    const activo = e.target.checked;
    estado.ingredientes[layer] = activo;
    toggleCapa(layer, activo);

    const fila = e.target.closest('.ingredient-item');
    if (fila) {
      if (activo) {
        fila.classList.remove('desactivado');
      } else {
        fila.classList.add('desactivado');
      }
    }

    actualizarResumen();
  });
});

// Event listeners para Aderezos y Salsas
document.querySelectorAll('.toggle-aderezo').forEach(cb => {
  cb.addEventListener('change', (e) => {
    const adKey = e.target.dataset.aderezo;
    if (adKey && estado.aderezos[adKey] !== undefined) {
      estado.aderezos[adKey] = e.target.checked;
      const fila = e.target.closest('.ingredient-item');
      if (fila) {
        if (e.target.checked) {
          fila.classList.remove('desactivado');
        } else {
          fila.classList.add('desactivado');
        }
      }
      actualizarResumen();
    }
  });
});

// Event listeners para Acompañamiento
document.querySelectorAll('input[name="acompanamiento"]').forEach(radio => {
  radio.addEventListener('change', (e) => {
    const tipo = e.target.value;
    estado.acompanamiento = tipo;

    actualizarTarjetasAcomp(tipo);
    actualizarBurbuja(tipo);
    actualizarResumen();
  });
});

document.querySelectorAll('.acomp-card').forEach(card => {
  card.addEventListener('click', () => {
    const radio = card.querySelector('input[type="radio"]');
    if (radio) {
      radio.checked = true;
      radio.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
});

// Botón Agregar al Pedido
btnAgregar?.addEventListener('click', () => {
  const activos = Object.entries(estado.ingredientes)
    .filter(([, v]) => v)
    .map(([k]) => k);

  // Asegurar pan siempre presente
  if (!activos.includes('pan')) {
    activos.unshift('pan');
  }

  const aderezosElegidos = Object.entries(estado.aderezos)
    .filter(([, v]) => v)
    .map(([k]) => k);

  btnAgregar.disabled = true;
  btnAgregar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Agregando...';

  fetch('agregar_personalizado.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      ingredientes: activos,
      aderezos: aderezosElegidos,
      acompanamiento: estado.acompanamiento,
    }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }
      if (data.ok) {
        btnAgregar.innerHTML = '✅ ¡Agregado al carrito!';
        btnAgregar.style.background = '#2E7D32';

        if (typeof mostrarToast === 'function') {
          mostrarToast('¡Hamburguesa Creada!', 'Se agregó tu combinación personalizada al pedido.');
        }
        const badge = document.getElementById('headerCartCount');
        if (badge && data.total_items) {
          badge.innerText = data.total_items;
          badge.style.display = 'inline-block';
        }
        const totalH = document.querySelector('.btn-cart-header .cart-total');
        if (totalH && data.total_formateado) {
          totalH.innerText = data.total_formateado;
        }

        setTimeout(() => {
          btnAgregar.innerHTML = 'AGREGAR AL PEDIDO <span class="btn-arrow">→</span>';
          btnAgregar.style.background = '';
          btnAgregar.disabled = false;
        }, 2200);
      } else {
        btnAgregar.innerHTML = '⚠️ ' + (data.error || 'No se pudo agregar');
        btnAgregar.style.background = '#F5A623';
        setTimeout(() => {
          btnAgregar.innerHTML = 'AGREGAR AL PEDIDO <span class="btn-arrow">→</span>';
          btnAgregar.style.background = '';
          btnAgregar.disabled = false;
        }, 2200);
      }
    })
    .catch(() => {
      btnAgregar.innerHTML = '⚠️ Error de conexión';
      setTimeout(() => {
        btnAgregar.innerHTML = 'AGREGAR AL PEDIDO <span class="btn-arrow">→</span>';
        btnAgregar.disabled = false;
      }, 2200);
    });
});

(function init() {
  actualizarBurbuja(estado.acompanamiento);
  actualizarTarjetasAcomp(estado.acompanamiento);
  actualizarResumen();
})();