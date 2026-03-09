document.addEventListener('DOMContentLoaded', function() {
    const eventsGrid = document.querySelector('.em-events-grid');
    if (!eventsGrid) return;

    const count = eventsGrid.dataset.count || 3;
    const showMap = eventsGrid.dataset.showmap || 1;
    const showImage = eventsGrid.dataset.showimage || 1;
    const mapProvider = eventsGrid.dataset.mapprovider || 'yandex';

    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;

    const dateFilter = document.getElementById('em_date_filter');
    const cityFilter = document.getElementById('em_city_filter');
    const typeFilter = document.getElementById('em_type_filter');
    const applyBtn = document.getElementById('em_apply_filters');

    function createModal(contentHTML) {
        const existing = document.getElementById('em-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'em-modal';
        modal.style.cssText = `
            display: block;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 999999;
            overflow: auto;
        `;

        const content = document.createElement('div');
        content.style.cssText = `
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px 25px;
            animation: modalFadeIn 0.3s;
        `;

        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = `
            position: absolute;
            right: 15px;
            top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            z-index: 10;
        `;
        closeBtn.onclick = function() { modal.remove(); };
        closeBtn.onmouseover = function() { this.style.color = '#000'; };
        closeBtn.onmouseout = function() { this.style.color = '#aaa'; };

        content.innerHTML = contentHTML;
        content.insertBefore(closeBtn, content.firstChild);

        const style = document.createElement('style');
        style.textContent = `
            @keyframes modalFadeIn {
                from { opacity: 0; transform: translate(-50%, -40%); }
                to { opacity: 1; transform: translate(-50%, -50%); }
            }
        `;
        document.head.appendChild(style);

        modal.appendChild(content);
        document.body.appendChild(modal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.remove();
        });
    }

    function loadEvents(page, append = false, filters = {}) {
        if (isLoading) return;
        isLoading = true;

        const formData = new FormData();
        formData.append('action', 'load_more_events');
        formData.append('nonce', em_ajax.nonce);
        formData.append('page', page);
        formData.append('count', count);
        formData.append('showmap', showMap);
        formData.append('showimage', showImage);
        formData.append('mapprovider', mapProvider);
        formData.append('date_filter', filters.date || 'future');
        formData.append('city_filter', filters.city || '');
        formData.append('type_filter', filters.type || '');

        fetch(em_ajax.url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (append) {
                    eventsGrid.insertAdjacentHTML('beforeend', data.data.html);
                } else {
                    eventsGrid.innerHTML = data.data.html;
                }

                hasMore = data.data.has_more;
                currentPage = data.data.next_page;

                updateLoadMoreButton(hasMore, currentPage, data.data.max_pages);
            } else {
                alert('Ошибка загрузки данных');
            }
            isLoading = false;
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Ошибка соединения');
            isLoading = false;
        });
    }

    function updateLoadMoreButton(hasMore, nextPage, maxPages) {
        let button = document.querySelector('.em-load-more');
        if (!hasMore) {
            if (button) button.remove();
            return;
        }

        if (!button) {
            button = document.createElement('button');
            button.className = 'em-load-more';
            button.textContent = 'Показать больше событий';
            eventsGrid.insertAdjacentElement('afterend', button);
        } else {
            button.style.display = 'inline-block';
        }

        button.dataset.page = nextPage;
        button.dataset.max = maxPages;
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('em-load-more')) {
            e.preventDefault();
            const filters = {
                date: dateFilter ? dateFilter.value : 'future',
                city: cityFilter ? cityFilter.value : '',
                type: typeFilter ? typeFilter.value : ''
            };
            loadEvents(currentPage, true, filters);
        }
    });

    document.addEventListener('click', function(e) {
        const card = e.target.closest('.em-event-card');
        if (card) {
            e.preventDefault();
            const eventId = card.dataset.eventId;
            const descDiv = document.getElementById('em-desc-' + eventId);
            if (descDiv) {
                createModal(descDiv.innerHTML);
            }
        }
    });

    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const filters = {
                date: dateFilter.value,
                city: cityFilter.value,
                type: typeFilter.value
            };
            currentPage = 1;
            loadEvents(currentPage, false, filters);
        });
    }

    const initialFilters = {
        date: dateFilter ? dateFilter.value : 'future',
        city: cityFilter ? cityFilter.value : '',
        type: typeFilter ? typeFilter.value : ''
    };
    loadEvents(currentPage, false, initialFilters);
});