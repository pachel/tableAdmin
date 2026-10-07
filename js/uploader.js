document.addEventListener('DOMContentLoaded', () => {
    // 1. Gomb megkeresése (ha van ID, azzal, ha nincs, attribútum alapján)
    const btn = document.getElementById('fajlok-btn');
    if (!btn) return;
    // Megakadályozzuk az esetleges form beküldést, ha a típus nem lenne explicit 'button'
    btn.type = 'button';
    // 2. Dinamikus elemek felépítése
    const parent = btn.parentElement;
    // Rejtett tallózó input
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.multiple = true;
    fileInput.style.display = 'none';
    // Dropdown konténer
    const dropdown = document.createElement('div');
    dropdown.className = 'custom-file-dropdown';
    const fileList = document.createElement('div');
    const actionBox = document.createElement('div');
    actionBox.className = 'upload-action-box';
    const browseTriggerBtn = document.createElement('button');
    browseTriggerBtn.type = 'button';
    browseTriggerBtn.textContent = '+ Fájl tallózása';
    actionBox.appendChild(browseTriggerBtn);
    dropdown.appendChild(fileList);
    dropdown.appendChild(actionBox);
    // Elemek beszúrása a DOM-ba
    parent.appendChild(fileInput);
    parent.appendChild(dropdown);
    // 3. Eseménykezelők hozzárendelése (inline handler mentesen)
    // Dropdown nyitás / zárás a generált gombra kattintva
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });
    dropdown.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    window.addEventListener('click', () => {
        dropdown.classList.remove('show');
    });
    // Tallózás indítása a menün belüli gombról
    browseTriggerBtn.addEventListener('click', () => {
        fileInput.click();
    });
    // Fájl kiválasztása -> azonnali AJAX küldés
    fileInput.addEventListener('change', () => {
        uploadFiles(fileInput.files);
    });
    // Törlés gombok eseménykezelése delegálással
    fileList.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.delete-btn');
        if (delBtn) {
            const fileName = delBtn.getAttribute('data-filename');
            if (fileName) {
                deleteFile(fileName);
            }
        }
    });
    // 4. AJAX műveletek
    async function loadFiles() {
        try {
            const res = await fetch(ajax_list_url);
            const data = await res.json();
            if (data.status === 'ok') {
                renderFiles(data.files);
            }
        } catch (err) {
            console.error('Hiba a fájlok betöltésekor:', err);
        }
    }

    function renderFiles(files) {

        // Gomb feliratának dinamikus cseréje
        btn.innerHTML = "<i class=\"far fa-folder-open\"></i> " + (files.length > 0
            ? `Dokumentumok (${files.length}) ▾`
            : `Dokumentumok tallózása (0) ▾`);

        fileList.innerHTML = '';
        if (files.length === 0) {
            fileList.innerHTML = '<div style="padding: 10px; color: #888; font-size: 12px; text-align: center;">Nincsenek feltöltött fájlok.</div>';
            return;
        }

        files.forEach(file => {

            const item = document.createElement('div');
            item.className = 'file-item';

            const nameSpan = document.createElement('a');
            nameSpan.href = file.link;
            nameSpan.target = '_blank';
            nameSpan.className = 'file-name';
            nameSpan.title = file.name+"."+file.mime;
            nameSpan.textContent = file.name+"."+file.mime;

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'delete-btn';
            delBtn.setAttribute('data-filename', file.id);
            delBtn.textContent = 'Törlés';

            item.appendChild(nameSpan);
            item.appendChild(delBtn);
            fileList.appendChild(item);
        });
    }

    async function uploadFiles(files) {
        if (!files || files.length === 0) return;

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        try {
            const res = await fetch(ajax_upload_url, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'ok') {
                await loadFiles();
            } else {
                alert('Hiba a feltöltésnél: ' + (data.message || ''));
            }
        } catch (err) {
            console.error('Feltöltési hiba:', err);
        } finally {
            fileInput.value = '';
        }
    }

    async function deleteFile(fileName) {
        if (!confirm(`Biztosan törölni szeretnéd a(z) "${fileName}" fájlt?`)) return;

        const formData = new FormData();
        formData.append('id', fileName);
        try {
            const res = await fetch(ajax_delete_url, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'ok') {
                await loadFiles();
            } else {
                alert('Hiba a törlésnél: ' + data.message);
            }
        } catch (err) {
            console.error('Törlési hiba:', err);
        }
    }

    // Kezdő lekérés a form megjelenésekor
    loadFiles();
});