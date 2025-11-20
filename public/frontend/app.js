const API_BASE = '/backend/api'

function select(selector, root = document) {
    return root.querySelector(selector)
}
function selectAll(selector, root = document) {
    return Array.from(root.querySelectorAll(selector))
}

// Отображение нужного контента, с возможностью скрытия остального, можно разделить на страницы, можно сделат ьв рамках одной(тогда лучше всего использовать SPA, например vue)
function showView(id) {
    const viewIds = ['view-auth', 'view-patients']
    viewIds.forEach(viewId => {
        const element = document.getElementById(viewId)
        if (!element) return
        if (viewId === id) {
            element.style.display = ''
        } else {
            element.style.display = 'none'
        }
    })
}

// Основной метод, который будем переиспользовать для общения с нашей ипровизированный API
async function apiFetch(path, options = {}) {
    options.credentials = 'include'
    if (!options.headers) {
        options.headers = {}
    }
    if (options.body && !(options.body instanceof FormData)) {
        options.headers['Content-Type'] = 'application/json'
    }
    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
        options.body = JSON.stringify(options.body)
    }
    const fetchResponse = await fetch(API_BASE + path, options)
    let json = null
    try {
        json = await fetchResponse.json()
    } catch (err) {
        // игнорируем не-json ответы
    }
    return { ok: fetchResponse.ok, status: fetchResponse.status, json }
}
// Проверка аутентификации(см. похожий комментарий в index.php)
async function checkAuth() {
    const response = await apiFetch('/check-auth')
    return response
}
// Вход
async function doLogin(event) {
    if (event) {
        event.preventDefault()
    }
    const formEl = document.getElementById('loginForm')
    const email = select('input[name=email]', formEl).value.trim()
    const password = select('input[name=password]', formEl).value
    const errorElement = document.getElementById('loginError')
    errorElement.textContent = ''
    const response = await apiFetch('/login', { method: 'POST', body: { email, password } })
    if (response.ok) {
        initApp()
    } else {
        if (response.json && response.json.error) {
            errorElement.textContent = response.json.error
        } else {
            errorElement.textContent = 'Ошибка входа'
        }
    }
}
// Регистрация
async function doRegister(event) {
    if (event) {
        event.preventDefault()
    }
    const formEl = document.getElementById('registerForm')
    const name = select('input[name=name]', formEl).value.trim()
    const email = select('input[name=email]', formEl).value.trim()
    const password = select('input[name=password]', formEl).value
    const errorElement = document.getElementById('registerError')
    errorElement.textContent = ''
    const response = await apiFetch('/register', { method: 'POST', body: { name, email, password } })
    if (response.ok) {
        initApp()
    } else {
        if (response.json && response.json.error) {
            errorElement.textContent = response.json.error
        } else {
            errorElement.textContent = 'Ошибка регистрации'
        }
    }
}
// Выход
async function doLogout() {
    await apiFetch('/logout', { method: 'POST' })
    // Очищаем интерфейс
    const authBox = document.getElementById('authBox')
    if (authBox) authBox.innerHTML = ''
    const patientsList = document.getElementById('patientsList')
    if (patientsList) patientsList.innerHTML = ''
    window.currentPatientId = null
    showView('view-auth')
}
// Логика работы с пациентами
let patientsCache = []
// Получение пациентов
async function fetchPatients() {
    const response = await apiFetch('/patients')
    if (!response.ok) {
        if (response.json && response.json.error) {
            alert(response.json.error)
        } else {
            alert('Ошибка получения пациентов')
        }
        return
    }
    if (response.json) {
        patientsCache = response.json
    } else {
        patientsCache = []
    }
    renderPatients()
}
// Отображение пациентов
function renderPatients() {
    const root = document.getElementById('patientsList')
    root.innerHTML = ''
    if (!patientsCache.length) {
        root.innerHTML = '<div>Пациентов нет</div>'
        return
    }
    // Аналогично, можно было бы сделать через vue или же несколько страниц, но так чутка проще, выводим список пациентов
    patientsCache.forEach(patient => {
        const item = document.createElement('div')
        item.className = 'patients__item'
        const title = document.createElement('div')
        title.className = 'patients__item-title'
        title.textContent = patient.name
        const sub = document.createElement('div')
        sub.className = 'patients__item-sub'
        let sessionsCount = 0
        if (patient.sessions_count) {
            sessionsCount = patient.sessions_count
        }
        sub.textContent = `Возраст: ${patient.age} — Сессий: ${sessionsCount}`
        const btns = document.createElement('div')
        btns.style.marginTop = '8px'
        const viewButton = document.createElement('button')
        viewButton.className = 'button'
        viewButton.textContent = 'Открыть'
        viewButton.onclick = function () {
            openPatient(patient)
        }
        btns.appendChild(viewButton)
        item.appendChild(title)
        item.appendChild(sub)
        item.appendChild(btns)
        root.appendChild(item)
    })
}

function openPatient(patient) {
    // Выводим у пациентов сессии
    select('#sessionsView').style.display = 'block'
    select('#patientForm').style.display = 'none'
    select('#view-patients').scrollIntoView()
    document.getElementById('sessionsPatientName').textContent = patient.name
    loadSessions(patient.id)
    window.currentPatientId = patient.id
}
// Включение/выключение формы создания пациента
function togglePatientForm(show) {
    const element = document.getElementById('patientForm')
    if (show) {
        element.style.display = 'block'
    } else {
        element.style.display = 'none'
    }
}
// Логи создания/сохранения пациента
async function savePatient() {
    const name = document.getElementById('patientName').value.trim()
    const age = document.getElementById('patientAge').value
    const genderRadios = document.querySelectorAll('input[name="patientGender"]:checked')
    let gender = ''
    if (genderRadios.length > 0) {
        gender = genderRadios[0].value
    }
    const err = document.getElementById('patientError')
    err.textContent = ''
    if (!name || age === '' || !gender) {
        err.textContent = 'Заполните все поля'
        return
    }
    const response = await apiFetch('/patients', { method: 'POST', body: { name, age: Number(age), gender } })
    if (response.ok) {
        togglePatientForm(false)
        fetchPatients()
    } else {
        if (response.json && response.json.error) {
            err.textContent = response.json.error
        } else {
            err.textContent = 'Ошибка создания пациента'
        }
    }
}
// Логика загрузки сессий по пациенту
async function loadSessions(patientId) {
    const response = await apiFetch(`/sessions?patient_id=${patientId}`)
    if (!response.ok) {
        if (response.json && response.json.error) {
            alert(response.json.error)
        } else {
            alert('Ошибка загрузки сессий')
        }
        return
    }
    if (response.json) {
        renderSessions(response.json)
    } else {
        renderSessions([])
    }
}
// Отображение списка сессий
function renderSessions(list) {
    const root = document.getElementById('sessionsList')
    root.innerHTML = ''
    if (!list.length) {
        root.innerHTML = '<div>Сессий нет</div>'
    }
    list.forEach(session => {
        const item = document.createElement('div')
        item.className = 'sessions__item'
        const row = document.createElement('div')
        row.className = 'sessions__item-row'
        const left = document.createElement('div')
        left.textContent = `SUD: ${session.sud_score}, Quality: ${session.quality_score}`
        const right = document.createElement('div')
        let sessionDate = ''
        if (session.session_date) {
            sessionDate = session.session_date
        }
        right.textContent = sessionDate
        row.appendChild(left)
        row.appendChild(right)
        const comments = document.createElement('div')
        let commentText = ''
        if (session.comments) {
            commentText = session.comments
        }
        comments.textContent = commentText
        item.appendChild(row)
        item.appendChild(comments)
        root.appendChild(item)
    })
}
// Логика создания/сохранения сессий
async function saveSession() {
    const sud = document.getElementById('sessionSud').value
    const quality = document.getElementById('sessionQuality').value
    const comments = document.getElementById('sessionComments').value
    const err = document.getElementById('sessionError')
    err.textContent = ''
    const patientId = window.currentPatientId
    if (!patientId) {
        err.textContent = 'Пациент не выбран'
        return
    }
    const response = await apiFetch('/sessions', {
        method: 'POST',
        body: { patient_id: patientId, sud_score: Number(sud), quality_score: Number(quality), comments },
    })
    if (response.ok) {
        loadSessions(patientId)
        document.getElementById('sessionSud').value = '5'
        document.getElementById('sessionSudValue').textContent = '5'
        document.getElementById('sessionQuality').value = '5'
        document.getElementById('sessionQualityValue').textContent = '5'
        document.getElementById('sessionComments').value = ''
    } else {
        if (response.json && response.json.error) {
            err.textContent = response.json.error
        } else {
            err.textContent = 'Ошибка создания сессии'
        }
    }
}

// Централизованный обработчик событий
function attachEventHandlers() {
    // Показ кнопок авторизации и регистрации
    select('#showRegister').onclick = function (e) {
        e.preventDefault()
        select('#loginForm').style.display = 'none'
        select('#registerForm').style.display = 'block'
    }
    select('#showLogin').onclick = function (e) {
        e.preventDefault()
        select('#loginForm').style.display = 'block'
        select('#registerForm').style.display = 'none'
    }
    select('#loginForm').onsubmit = doLogin
    select('#registerForm').onsubmit = doRegister

    // Логика работы с показом форм пациентов
    select('#btnNewPatient').onclick = function () {
        togglePatientForm(true)
    }
    select('#cancelPatient').onclick = function () {
        togglePatientForm(false)
    }
    select('#savePatient').onclick = savePatient
    select('#backToPatients').onclick = function () {
        select('#sessionsView').style.display = 'none'
        window.currentPatientId = null
    }
    select('#saveSession').onclick = saveSession

    // Обработчики для ползунков SUD и Quality
    const sudSlider = select('#sessionSud')
    if (sudSlider) {
        sudSlider.addEventListener('input', function () {
            const sudValue = select('#sessionSudValue')
            if (sudValue) {
                sudValue.textContent = this.value
            }
        })
    }

    const qualitySlider = select('#sessionQuality')
    if (qualitySlider) {
        qualitySlider.addEventListener('input', function () {
            const qualityValue = select('#sessionQualityValue')
            if (qualityValue) {
                qualityValue.textContent = this.value
            }
        })
    }
}

async function initApp() {
    const authResponse = await checkAuth()
    if (authResponse.ok && authResponse.json && authResponse.json.authenticated) {
        let therapistName = ''
        if (authResponse.json.therapist) {
            therapistName = authResponse.json.therapist.name
        }
        document.getElementById(
            'authBox',
        ).innerHTML = `<span>Привет, ${therapistName}</span> <button class=\"button\" id=\"btnLogout\">Выйти</button>`
        select('#btnLogout').onclick = doLogout
        showView('view-patients')
        fetchPatients()
    } else {
        // ensure auth UI is cleared when not authenticated
        const authBox = document.getElementById('authBox')
        if (authBox) authBox.innerHTML = ''
        showView('view-auth')
    }
}
// Инициализация приложения после загрузки DOM(грубо говоря, собираем здесь все воедино используя данное событие, как точку входа, на подобии того как работает react или vue)
window.addEventListener('DOMContentLoaded', function () {
    attachEventHandlers()
    initApp()
})
