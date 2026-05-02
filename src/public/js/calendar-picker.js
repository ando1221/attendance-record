/**
 * 自作カレンダーピッカー
 * - mode=month: 月選択
 * - mode=date: 日付選択
 */
document.addEventListener('DOMContentLoaded', function () {
    const pickers = document.querySelectorAll('[data-calendar-picker]');

    pickers.forEach(function (picker) {
        const mode = picker.dataset.calendarMode;
        const hiddenInput = picker.querySelector('[data-calendar-hidden]');
        const trigger = picker.querySelector('[data-calendar-trigger]');
        const panel = picker.querySelector('[data-calendar-panel]');
        const body = picker.querySelector('[data-calendar-body]');
        const current = picker.querySelector('[data-calendar-current]');
        const label = picker.querySelector('[data-calendar-label]');
        const prevButton = picker.querySelector('[data-calendar-prev]');
        const nextButton = picker.querySelector('[data-calendar-next]');

        let selectedDate;
        let displayDate;

        if (mode === 'month') {
            selectedDate = parseMonth(hiddenInput.value);
            displayDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        } else {
            selectedDate = parseDate(hiddenInput.value);
            displayDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        }

        function parseDate(value) {
            const [year, month, day] = value.split('-').map(Number);
            return new Date(year, month - 1, day);
        }

        function parseMonth(value) {
            const [year, month] = value.split('-').map(Number);
            return new Date(year, month - 1, 1);
        }

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function formatMonth(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            return `${year}-${month}`;
        }

        function updateLabel() {
            if (mode === 'month') {
                label.textContent =
                    `${selectedDate.getFullYear()}/${String(selectedDate.getMonth() + 1).padStart(2, '0')}`;
            } else {
                label.textContent =
                    `${selectedDate.getFullYear()}/${String(selectedDate.getMonth() + 1).padStart(2, '0')}/${String(selectedDate.getDate()).padStart(2, '0')}`;
            }
        }

        function closePanel() {
            panel.hidden = true;
        }

        function openPanel() {
            panel.hidden = false;
            render();
        }

        function renderMonthPicker() {
            current.textContent = `${displayDate.getFullYear()}年`;

            const monthNames = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];

            body.innerHTML = `
        <div class="calendar-picker__months">
          ${monthNames.map(function (monthName, index) {
                const isSelected =
                    selectedDate.getFullYear() === displayDate.getFullYear() &&
                    selectedDate.getMonth() === index;

                return `
              <button
                type="button"
                class="calendar-picker__cell ${isSelected ? 'calendar-picker__cell--selected' : ''}"
                data-month-index="${index}">
                ${monthName}
              </button>
            `;
            }).join('')}
        </div>
      `;

            body.querySelectorAll('[data-month-index]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const monthIndex = Number(button.dataset.monthIndex);
                    selectedDate = new Date(displayDate.getFullYear(), monthIndex, 1);
                    hiddenInput.value = formatMonth(selectedDate);
                    updateLabel();
                    closePanel();
                    picker.submit();
                });
            });
        }

        function renderDatePicker() {
            current.textContent = `${displayDate.getFullYear()}年${displayDate.getMonth() + 1}月`;

            const weekdays = ['日', '月', '火', '水', '木', '金', '土'];

            const firstDay = new Date(displayDate.getFullYear(), displayDate.getMonth(), 1);
            const startDay = firstDay.getDay();
            const daysInMonth = new Date(displayDate.getFullYear(), displayDate.getMonth() + 1, 0).getDate();
            const prevMonthDays = new Date(displayDate.getFullYear(), displayDate.getMonth(), 0).getDate();

            let cells = '';

            for (let i = 0; i < 42; i++) {
                let cellDate;
                let isCurrentMonth = true;

                if (i < startDay) {
                    cellDate = new Date(displayDate.getFullYear(), displayDate.getMonth() - 1, prevMonthDays - startDay + i + 1);
                    isCurrentMonth = false;
                } else if (i >= startDay + daysInMonth) {
                    cellDate = new Date(displayDate.getFullYear(), displayDate.getMonth() + 1, i - startDay - daysInMonth + 1);
                    isCurrentMonth = false;
                } else {
                    cellDate = new Date(displayDate.getFullYear(), displayDate.getMonth(), i - startDay + 1);
                }

                const dayOfWeek = cellDate.getDay();
                const isSunday = dayOfWeek === 0;
                const isSaturday = dayOfWeek === 6;
                const isSelected =
                    selectedDate.getFullYear() === cellDate.getFullYear() &&
                    selectedDate.getMonth() === cellDate.getMonth() &&
                    selectedDate.getDate() === cellDate.getDate();

                cells += `
          <button
            type="button"
            class="calendar-picker__cell
              ${!isCurrentMonth ? 'calendar-picker__cell--muted' : ''}
              ${isSunday ? 'calendar-picker__cell--sun' : ''}
              ${isSaturday ? 'calendar-picker__cell--sat' : ''}
              ${isSelected ? 'calendar-picker__cell--selected' : ''}"
            data-date-value="${formatDate(cellDate)}">
            ${cellDate.getDate()}
          </button>
        `;
            }

            body.innerHTML = `
        <div class="calendar-picker__weekdays">
          ${weekdays.map(function (weekday, index) {
                let className = 'calendar-picker__weekday';
                if (index === 0) className += ' calendar-picker__weekday--sun';
                if (index === 6) className += ' calendar-picker__weekday--sat';
                return `<div class="${className}">${weekday}</div>`;
            }).join('')}
        </div>
        <div class="calendar-picker__dates">
          ${cells}
        </div>
      `;

            body.querySelectorAll('[data-date-value]').forEach(function (button) {
                button.addEventListener('click', function () {
                    selectedDate = parseDate(button.dataset.dateValue);
                    hiddenInput.value = formatDate(selectedDate);
                    updateLabel();
                    closePanel();
                    picker.submit();
                });
            });
        }

        function render() {
            if (mode === 'month') {
                renderMonthPicker();
            } else {
                renderDatePicker();
            }
        }

        trigger.addEventListener('click', function () {
            if (panel.hidden) {
                openPanel();
            } else {
                closePanel();
            }
        });

        prevButton.addEventListener('click', function () {
            if (mode === 'month') {
                displayDate = new Date(displayDate.getFullYear() - 1, 0, 1);
            } else {
                displayDate = new Date(displayDate.getFullYear(), displayDate.getMonth() - 1, 1);
            }
            render();
        });

        nextButton.addEventListener('click', function () {
            if (mode === 'month') {
                displayDate = new Date(displayDate.getFullYear() + 1, 0, 1);
            } else {
                displayDate = new Date(displayDate.getFullYear(), displayDate.getMonth() + 1, 1);
            }
            render();
        });

        document.addEventListener('click', function (event) {
            if (!picker.contains(event.target)) {
                closePanel();
            }
        });

        updateLabel();
    });
});