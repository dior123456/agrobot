const API_URL = 'http://localhost/agrobot/backend/php/api.php';
let usuarioActual = null;

async function apiRequest(action, method, data = null) {
    const url = `${API_URL}?action=${action}`;
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        console.log(` API Request: ${method} ${action}`, data);
        const response = await fetch(url, options);
        const result = await response.json();
        console.log(` API Response:`, result);
        return result;
    } catch (error) {
        console.error(' API Error:', error);
        return { success: false, message: 'Error de conexión: ' + error.message };
    }
}

// ==================== LOGIN ====================
async function iniciarSesion(nombre, codigo, rol) {
    const res = await apiRequest('login', 'POST', { nombre, codigo });
    if (res.success && res.data && res.data.rol === rol && res.data.estado === 'activo') {
        usuarioActual = res.data;
        localStorage.setItem('agrobot_usuario', JSON.stringify(usuarioActual));
        return { success: true, usuario: usuarioActual };
    }
    return { success: false, message: res.message || 'Credenciales incorrectas' };
}

async function registrarse(nombre, codigo) {
    return await apiRequest('register', 'POST', { nombre, codigo });
}

function cerrarSesion() {
    usuarioActual = null;
    localStorage.removeItem('agrobot_usuario');
    document.getElementById('loginPanel').style.display = 'block';
    document.getElementById('userPanel').style.display = 'none';
    document.getElementById('adminPanel').style.display = 'none';
    document.getElementById('nombre').value = '';
    document.getElementById('codigo').value = '';
}

// ==================== CHAT ====================
function detectarIdioma(texto) {
    const t = texto.toLowerCase();
    if (t.includes('mbolo') || t.includes('nga') || t.includes('yebela')) return 'fang';
    if (t.includes('b') || t.includes('boto') || t.includes('ripoto')) return 'bubi';
    return 'español';
}

function agregarMensaje(texto, tipo) {
    const chat = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = tipo === 'user' ? 'user-msg' : 'bot-msg';
    div.innerHTML = texto.replace(/\n/g, '<br>');
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

function mostrarLoading() {
    const chat = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.id = 'loadingMsg';
    div.className = 'bot-msg';
    div.innerHTML = 'AgroBot está escribiendo...';
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

function quitarLoading() {
    const loading = document.getElementById('loadingMsg');
    if (loading) loading.remove();
}

async function enviarMensaje() {
    const mensajeInput = document.getElementById('userMessage');
    const mensaje = mensajeInput.value.trim();
    if (!mensaje) return;
    
    // Obtener imagen y texto adjunto
    const imagenFile = document.getElementById('imagenAdjunta').files[0];
    let imagen_url = null;
    if (imagenFile) {
        imagen_url = URL.createObjectURL(imagenFile);
        agregarMensaje(` Imagen adjunta: ${imagenFile.name}`, 'user');
    }
    
    const texto_adjunto = document.getElementById('textoAdjunto').value.trim();
    if (texto_adjunto) {
        agregarMensaje(`📎 Texto adicional: ${texto_adjunto}`, 'user');
    }
    
    // Agregar mensaje del usuario
    agregarMensaje(mensaje, 'user');
    
    // Limpiar inputs
    mensajeInput.value = '';
    document.getElementById('textoAdjunto').value = '';
    document.getElementById('imagenAdjunta').value = '';
    
    // Mostrar loading
    mostrarLoading();
    
    // Detectar idioma
    const idioma = detectarIdioma(mensaje);
    
    // Enviar a la API
    const res = await apiRequest('chat', 'POST', {
        mensaje: mensaje,
        usuario_id: usuarioActual?.id || null,
        usuario_nombre: usuarioActual?.nombre || 'Anónimo',
        idioma: idioma,
        imagen_url: imagen_url,
        texto_adjunto: texto_adjunto
    });
    
    quitarLoading();
    
    if (res.success && res.data && res.data.respuesta) {
        agregarMensaje(res.data.respuesta, 'bot');
        guardarHistorial(mensaje, res.data.respuesta);
    } else {
        agregarMensaje(' Error al procesar tu consulta. Por favor, intenta de nuevo.', 'bot');
        console.error('Error detallado:', res);
    }
}

function guardarHistorial(consulta, respuesta) {
    let historial = JSON.parse(localStorage.getItem('agrobot_historial')) || [];
    historial.unshift({
        consulta: consulta,
        respuesta: respuesta.substring(0, 80),
        fecha: new Date().toLocaleTimeString()
    });
    historial = historial.slice(0, 10);
    localStorage.setItem('agrobot_historial', JSON.stringify(historial));
    actualizarHistorial();
}

function actualizarHistorial() {
    const lista = document.getElementById('historialLista');
    if (!lista) return;
    
    const historial = JSON.parse(localStorage.getItem('agrobot_historial')) || [];
    if (historial.length === 0) {
        lista.innerHTML = '<li>No hay consultas previas</li>';
    } else {
        lista.innerHTML = historial.map(h => 
            `<li> "${h.consulta.substring(0, 50)}..." - ${h.fecha}</li>`
        ).join('');
    }
}

function limpiarChat() {
    const chat = document.getElementById('chatMessages');
    chat.innerHTML = '<div class="bot-msg">🌱 Chat limpiado. ¿En qué más puedo ayudarte?</div>';
}

// ==================== ADMIN: TABLAS ====================
async function cargarTabla(tabla, contenedorId, campos) {
    const res = await apiRequest(tabla, 'GET');
    if (!res.success || !res.data) {
        console.error(`Error cargando ${tabla}:`, res);
        return;
    }
    
    const tbody = document.querySelector(`#${contenedorId} tbody`);
    if (!tbody) return;
    
    if (res.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${campos.length + 1}">No hay registros</td></tr>`;
        return;
    }
    
    tbody.innerHTML = res.data.map(item => `
        <tr>
            ${campos.map(c => `<td data-label="${c}">${item[c] || '-'}</td>`).join('')}
            <td><button onclick="eliminarRegistro('${tabla}', ${item.id})" style="background:#c62828; padding:5px 10px;">🗑️</button></td>
        </tr>
    `).join('');
}

window.eliminarRegistro = async function(tabla, id) {
    if (confirm('¿Eliminar este registro?')) {
        const res = await apiRequest(tabla, 'DELETE', { id });
        if (res.success) {
            await cargarTodasTablas();
        } else {
            alert('Error: ' + (res.message || 'No se pudo eliminar'));
        }
    }
};

async function crearRegistro(tabla, datos) {
    if (!datos || Object.values(datos).every(v => !v)) {
        alert('Completa al menos un campo');
        return;
    }
    const res = await apiRequest(tabla, 'POST', datos);
    if (res.success) {
        await cargarTodasTablas();
        alert('Registro creado correctamente');
        // Limpiar formularios
        document.querySelectorAll(`#tab-${tabla} input, #tab-${tabla} textarea, #tab-${tabla} select`).forEach(el => {
            if (el.type !== 'button') el.value = '';
        });
    } else {
        alert('Error: ' + (res.message || 'No se pudo crear'));
    }
}

async function cargarTodasTablas() {
    if (!usuarioActual || usuarioActual.rol !== 'admin') return;
    
    await cargarTabla('usuarios', 'tablaUsuarios', ['id', 'nombre', 'codigo', 'rol', 'estado']);
    await cargarTabla('respuestas', 'tablaRespuestas', ['id', 'palabra_clave', 'respuesta', 'idioma']);
    await cargarTabla('consultas', 'tablaConsultas', ['id', 'usuario_nombre', 'consulta', 'respuesta', 'fecha']);
    await cargarTabla('cultivos', 'tablaCultivos', ['id', 'nombre', 'nombre_cientifico', 'descripcion']);
    await cargarTabla('plagas', 'tablaPlagas', ['id', 'nombre', 'tipo', 'cultivo_afectado', 'tratamiento']);
    await cargarTabla('consejos', 'tablaConsejos', ['id', 'titulo', 'contenido', 'categoria']);
    await cargarTabla('logs', 'tablaLogs', ['id', 'usuario_id', 'accion', 'descripcion', 'fecha']);
    await cargarEstadisticas();
}

async function cargarEstadisticas() {
    const res = await apiRequest('estadisticas', 'GET');
    if (res.success && res.data) {
        const totalUsuarios = document.getElementById('totalUsuarios');
        const totalRespuestas = document.getElementById('totalRespuestas');
        const consultasHoy = document.getElementById('consultasHoy');
        if (totalUsuarios) totalUsuarios.innerText = res.data.total_usuarios || 0;
        if (totalRespuestas) totalRespuestas.innerText = res.data.total_respuestas || 0;
        if (consultasHoy) consultasHoy.innerText = res.data.consultas_hoy || 0;
    }
}

function initAdminEventListeners() {
    const crearUsuario = document.getElementById('crearUsuarioBtn');
    if (crearUsuario) {
        crearUsuario.onclick = () => crearRegistro('usuarios', {
            nombre: document.getElementById('user_nombre')?.value,
            codigo: document.getElementById('user_codigo')?.value,
            rol: document.getElementById('user_rol')?.value || 'user'
        });
    }
    
    const crearRespuesta = document.getElementById('crearRespuestaBtn');
    if (crearRespuesta) {
        crearRespuesta.onclick = () => crearRegistro('respuestas', {
            palabra_clave: document.getElementById('resp_palabra')?.value,
            respuesta: document.getElementById('resp_texto')?.value,
            idioma: document.getElementById('resp_idioma')?.value || 'español'
        });
    }
    
    const crearCultivo = document.getElementById('crearCultivoBtn');
    if (crearCultivo) {
        crearCultivo.onclick = () => crearRegistro('cultivos', {
            nombre: document.getElementById('cult_nombre')?.value,
            nombre_cientifico: document.getElementById('cult_cientifico')?.value,
            descripcion: document.getElementById('cult_desc')?.value
        });
    }
    
    const crearPlaga = document.getElementById('crearPlagaBtn');
    if (crearPlaga) {
        crearPlaga.onclick = () => crearRegistro('plagas', {
            nombre: document.getElementById('plag_nombre')?.value,
            tipo: document.getElementById('plag_tipo')?.value || 'plaga',
            cultivo_afectado: document.getElementById('plag_cultivo')?.value,
            sintomas: document.getElementById('plag_sintomas')?.value,
            tratamiento: document.getElementById('plag_tratamiento')?.value
        });
    }
    
    const crearConsejo = document.getElementById('crearConsejoBtn');
    if (crearConsejo) {
        crearConsejo.onclick = () => crearRegistro('consejos', {
            titulo: document.getElementById('cons_titulo')?.value,
            contenido: document.getElementById('cons_contenido')?.value,
            categoria: document.getElementById('cons_categoria')?.value || 'general'
        });
    }
}

function initTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(btn => {
        btn.onclick = () => {
            tabs.forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            const tabId = document.getElementById(`tab-${btn.dataset.tab}`);
            if (tabId) tabId.classList.add('active');
            cargarTodasTablas();
        };
    });
}

// ==================== NAVEGACIÓN ====================
async function cargarPanelUsuario() {
    const userNombre = document.getElementById('userNombre');
    if (userNombre) userNombre.innerText = usuarioActual.nombre;
    document.getElementById('loginPanel').style.display = 'none';
    document.getElementById('userPanel').style.display = 'block';
    actualizarHistorial();
}

async function cargarPanelAdmin() {
    const adminNombre = document.getElementById('adminNombre');
    if (adminNombre) adminNombre.innerText = usuarioActual.nombre;
    document.getElementById('loginPanel').style.display = 'none';
    document.getElementById('adminPanel').style.display = 'block';
    await cargarTodasTablas();
    initAdminEventListeners();
    initTabs();
}

// ==================== EVENTOS PRINCIPALES ====================
document.addEventListener('DOMContentLoaded', () => {
    console.log(' AgroBot iniciando...');
    
    // Login
    const iniciarBtn = document.getElementById('iniciarSesionBtn');
    if (iniciarBtn) {
        iniciarBtn.onclick = async () => {
            const nombre = document.getElementById('nombre').value.trim();
            const codigo = document.getElementById('codigo').value.trim();
            const rol = document.getElementById('rol').value;
            const msgDiv = document.getElementById('loginMensaje');
            
            if (!nombre || !codigo) {
                if (msgDiv) msgDiv.innerHTML = '<span style="color:#c62828;"> Completa todos los campos</span>';
                return;
            }
            
            const resultado = await iniciarSesion(nombre, codigo, rol);
            if (resultado.success) {
                if (rol === 'admin') {
                    await cargarPanelAdmin();
                } else {
                    await cargarPanelUsuario();
                }
            } else {
                if (msgDiv) msgDiv.innerHTML = `<span style="color:#c62828;"> ${resultado.message}</span>`;
            }
        };
    }
    
    // Registro
    const registrarBtn = document.getElementById('registrarseBtn');
    if (registrarBtn) {
        registrarBtn.onclick = async () => {
            const nombre = document.getElementById('nombre').value.trim();
            const codigo = document.getElementById('codigo').value.trim();
            const msgDiv = document.getElementById('loginMensaje');
            
            if (!nombre || !codigo) {
                if (msgDiv) msgDiv.innerHTML = '<span style="color:#c62828;">Completa nombre y código</span>';
                return;
            }
            
            const resultado = await registrarse(nombre, codigo);
            if (resultado.success) {
                if (msgDiv) msgDiv.innerHTML = `<span style="color:#2e7d32;">${resultado.message}</span>`;
                document.getElementById('nombre').value = '';
                document.getElementById('codigo').value = '';
            } else {
                if (msgDiv) msgDiv.innerHTML = `<span style="color:#c62828;"> ${resultado.message}</span>`;
            }
        };
    }
    
    // Logout
    const logoutUser = document.getElementById('logoutUserBtn');
    if (logoutUser) logoutUser.onclick = cerrarSesion;
    
    const logoutAdmin = document.getElementById('logoutAdminBtn');
    if (logoutAdmin) logoutAdmin.onclick = cerrarSesion;
    
    // Chat
    const sendBtn = document.getElementById('sendMsgBtn');
    if (sendBtn) sendBtn.onclick = enviarMensaje;
    
    const clearBtn = document.getElementById('clearChatBtn');
    if (clearBtn) clearBtn.onclick = limpiarChat;
    
    const userMessage = document.getElementById('userMessage');
    if (userMessage) {
        userMessage.onkeypress = (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                enviarMensaje();
            }
        };
    }
    
    // Cargar sesión guardada
    const usuarioGuardado = localStorage.getItem('agrobot_usuario');
    if (usuarioGuardado) {
        try {
            usuarioActual = JSON.parse(usuarioGuardado);
            if (usuarioActual.rol === 'admin') {
                cargarPanelAdmin();
            } else if (usuarioActual.rol === 'user') {
                cargarPanelUsuario();
            }
        } catch (e) {
            console.error('Error al cargar sesión:', e);
        }
    }
    
    console.log(' AgroBot listo');
});