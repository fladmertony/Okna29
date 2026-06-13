document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;
    const themeBtn = document.getElementById('themeToggle');

    // Тема
    let isDark = localStorage.getItem('theme') === 'dark';
    html.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
    themeBtn?.addEventListener('click', () => {
        isDark = !isDark;
        html.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });

    // Валидация
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        const pass = form.querySelector('#password');
        const passConf = form.querySelector('input[name="password_confirm"]');
        if (pass && passConf) {
            passConf.addEventListener('input', () => passConf.setCustomValidity(passConf.value !== pass.value ? 'Пароли не совпадают' : ''));
        }
        form.addEventListener('submit', e => { if(!form.checkValidity()){ e.preventDefault(); e.stopPropagation(); } form.classList.add('was-validated'); });
    });

    // Генератор пароля (без изменений)
    const genBtn = document.getElementById('generatePassBtn');
    const passInput = document.getElementById('password');
    const passConfInput = document.querySelector('input[name="password_confirm"]');
    if (genBtn && passInput && passConfInput) {
        genBtn.addEventListener('click', () => {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789@#$!%&*';
            let p = ''; for(let i=0;i<12;i++) p += chars[Math.floor(Math.random()*chars.length)];
            p = p.replace(/[^A-Z]/,'A').replace(/[^0-9]/,'1').replace(/[^@#$!%&*]/,'!');
            passInput.value = passConfInput.value = p;
            passInput.type = passConfInput.type = 'text';
            passConfInput.setCustomValidity('');
            const orig = genBtn.innerHTML; genBtn.innerHTML = '✅ Скопируйте'; genBtn.classList.replace('btn-outline-secondary','btn-success');
            setTimeout(() => { genBtn.innerHTML = orig; genBtn.classList.replace('btn-success','btn-outline-secondary'); passInput.type = passConfInput.type = 'password'; }, 2500);
        });
    }

    // 🔹 КОНТЕКСТНЫЙ КАЛЬКУЛЯТОР
    const serviceSelect = document.getElementById('service_type');
    const calcWrapper = document.getElementById('calc_wrapper');
    const calcContainer = document.getElementById('calc_container');
    const calcAddBtn = document.getElementById('calc_add_btn');
    const calcTotalEl = document.getElementById('calc_total');
    const calcHiddenInput = document.getElementById('calc_json');

    // Матрица цен (базовая для Среднего размера и Поворотно-откидного)
    const basePrices = { '1': 5000, '2': 8500, '3': 13000, '4': 17000 };
    const typeNames = { '1': 'Одностворчатое', '2': 'Двухстворчатое', '3': 'Трехстворчатое', '4': 'Балконный блок' };
    const openingMult = { 'fixed': 0.85, 'turn': 1.0, 'tilt': 1.25 };
    const openingNames = { 'fixed': 'Глухое', 'turn': 'Поворотное', 'tilt': 'Поворотно-откидное' };
    const sizeMult = { '110x120': 0.9, '130x140': 1.0, '160x150': 1.15 };

    // Переключение видимости
    const toggleCalc = () => {
        const selected = serviceSelect.options[serviceSelect.selectedIndex];
        const needsCalc = selected?.dataset.needsCalc === '1';
        calcWrapper.style.display = needsCalc ? 'block' : 'none';
        if (!needsCalc) {
            calcContainer.innerHTML = '';
            calcTotalEl.textContent = '0 ₽';
            calcHiddenInput.value = '';
        }
    };
    if (serviceSelect) {
        serviceSelect.addEventListener('change', toggleCalc);
        toggleCalc(); // Проверка при загрузке (если передан GET)
    }

    if (calcContainer && calcAddBtn) {
        let rowId = 0;
        const createRow = () => {
            rowId++;
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-center calc-row mb-2 p-2 bg-body rounded';
            row.innerHTML = `
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm calc-type">
                        <option value="">Тип окна</option>
                        <option value="1">Одностворчатое</option>
                        <option value="2">Двухстворчатое</option>
                        <option value="3">Трехстворчатое</option>
                        <option value="4">Балконный блок</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm calc-opening">
                        <option value="fixed">Глухое</option>
                        <option value="turn">Поворотное</option>
                        <option value="tilt" selected>Поворотно-откидное</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm calc-size">
                        <option value="110x120">Маленькое (110×120 см)</option>
                        <option value="130x140" selected>Среднее (130×140 см)</option>
                        <option value="160x150">Большое (160×150 см)</option>
                    </select>
                </div>
                <div class="col-4 col-md-1">
                    <input type="number" class="form-control form-control-sm calc-qty" placeholder="Кол-во" min="1" max="20" value="1">
                </div>
                <div class="col-4 col-md-1">
                    <span class="calc-row-total fw-bold text-success small">0 ₽</span>
                </div>
                <div class="col-4 col-md-1 d-grid">
                    <button type="button" class="btn btn-sm btn-outline-danger" title="Удалить">×</button>
                </div>
            `;
            calcContainer.appendChild(row);
            // Навешиваем события
            row.querySelectorAll('select, input.calc-qty').forEach(el => el.addEventListener('input', updateCalc));
            row.querySelector('select.calc-type').addEventListener('change', updateCalc);
            row.querySelector('button.btn-outline-danger').addEventListener('click', () => { row.remove(); updateCalc(); });
            updateCalc();
        };

        const updateCalc = () => {
            let total = 0; const items = [];
            document.querySelectorAll('.calc-row').forEach(row => {
                const type = row.querySelector('.calc-type').value;
                const opening = row.querySelector('.calc-opening').value;
                const size = row.querySelector('.calc-size').value;
                const qty = parseInt(row.querySelector('.calc-qty').value) || 0;

                if (type && qty > 0 && basePrices[type]) {
                    const base = basePrices[type];
                    const price = Math.round(base * openingMult[opening] * sizeMult[size] * qty);
                    total += price;
                    row.querySelector('.calc-row-total').textContent = price.toLocaleString('ru-RU') + ' ₽';
                    items.push({ type: typeNames[type], opening: openingNames[opening], size: size.replace('x','×') + ' см', qty, basePrice: base, total: price });
                }
            });
            calcTotalEl.textContent = total.toLocaleString('ru-RU') + ' ₽';
            calcHiddenInput.value = JSON.stringify({ items, total });
        };

        calcAddBtn.addEventListener('click', createRow);
        createRow();
    }
});