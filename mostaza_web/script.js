
const INGREDIENTES = {
  pan:     { precio: 0,      label: 'Pan',     emoji: '🍞' },
  carne:   { precio: 2500,   label: 'Carne',   emoji: '🥩' },
  pepino:  {precio: 1500,   label: 'Pepino',   emoji: '🥒' },
  huevo:   {precio: 500,   label: 'Huevo',   emoji: '🍳' },
  queso:   { precio: 1000,    label: 'Queso',   emoji: '🧀' },
  lechuga: { precio: 500,    label: 'Lechuga', emoji: '🥬' },
  tomate:  { precio: 600,    label: 'Tomate',  emoji: '🍅' },
  tocino:  { precio: 1000,    label: 'Tocino',  emoji: '🥓' },
  cebolla:  { precio: 900,    label: 'Cebolla',  emoji: '🧅' },


};

const ACOMPAÑAMIENTOS = {
  papas:   { precio: 1500, label: 'Papas Fritas',     emoji: '🍟' },
  nuggets: { precio: 1800, label: 'Nuggets de Pollo', emoji: '🍗' },
};


const estado = {
  ingredientes: { pan: true, carne: true, queso: true, lechuga: true, tomate: true, pepino: true, huevo: true, tocino: true, cebolla: true },
  acompanamiento: 'papas',
};


const resumenTexto  = document.getElementById('resumenTexto');
const precioTotal   = document.getElementById('precioTotal');
const acompBubble   = document.getElementById('acompBubble');
const acompEmoji    = document.getElementById('acompEmoji');
const btnAgregar    = document.getElementById('btnAgregar');


function toggleCapa(layer, visible) {
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
  const activos = Object.entries(estado.ingredientes)
    .filter(([, v]) => v)
    .map(([k]) => `${INGREDIENTES[k].emoji} ${INGREDIENTES[k].label}`);

  const acomp = ACOMPAÑAMIENTOS[estado.acompanamiento];

  if (activos.length === 0) {
    resumenTexto.textContent = '¡Elegí al menos un ingrediente!';
  } else {
    resumenTexto.textContent = activos.join(' · ') + ` + ${acomp.emoji} ${acomp.label}`;
  }

  
  const totalIng = Object.entries(estado.ingredientes)
    .filter(([, v]) => v)
    .reduce((sum, [k]) => sum + INGREDIENTES[k].precio, 0);

  const total = totalIng + acomp.precio;
  precioTotal.textContent = total.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}


function actualizarBurbuja(tipo) {
  const acomp = ACOMPAÑAMIENTOS[tipo];
  acompEmoji.textContent = acomp.emoji;

  acompBubble.classList.remove('visible');
  setTimeout(() => acompBubble.classList.add('visible'), 40);
}


function actualizarTarjetasAcomp(valorSeleccionado) {
  document.querySelectorAll('.acomp-card').forEach(card => {
    const radio = card.querySelector('input[type="radio"]');
    if (radio.value === valorSeleccionado) {
      card.classList.add('activa');
    } else {
      card.classList.remove('activa');
    }
  });
}

document.querySelectorAll('.ingredient-list input[type="checkbox"]').forEach(checkbox => {
  checkbox.addEventListener('change', (e) => {
    const layer = e.target.dataset.layer;
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
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
  });
});


btnAgregar.addEventListener('click', () => {
  const activos = Object.entries(estado.ingredientes)
    .filter(([, v]) => v)
    .map(([k]) => k);

  if (activos.length === 0) {
    btnAgregar.textContent = '⚠️ ¡Elegí al menos un ingrediente!';
    btnAgregar.style.background = '#F5A623';
    setTimeout(() => {
      btnAgregar.innerHTML = 'AGREGAR AL PEDIDO <span class="btn-arrow">→</span>';
      btnAgregar.style.background = '';
    }, 2000);
    return;
  }

  btnAgregar.disabled = true;
  btnAgregar.innerHTML = 'Agregando...';

  fetch('agregar_personalizado.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      ingredientes: activos,
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