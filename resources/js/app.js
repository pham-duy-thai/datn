const navToggle = document.querySelector('[data-nav-toggle]');
const mainNav = document.querySelector('[data-main-nav]');

navToggle?.addEventListener('click', () => {
    mainNav?.classList.toggle('open');
});

const doctorSelect = document.querySelector('[data-doctor-select]');
const scheduleSelect = document.querySelector('[data-schedule-select]');

function syncScheduleOptions() {
    if (!doctorSelect || !scheduleSelect) {
        return;
    }

    const selectedDoctor = doctorSelect.value;

    Array.from(scheduleSelect.options).forEach((option) => {
        if (!option.dataset.doctor) {
            option.hidden = false;
            return;
        }

        const shouldShow = !selectedDoctor || option.dataset.doctor === selectedDoctor;
        option.hidden = !shouldShow;

        if (!shouldShow && option.selected) {
            scheduleSelect.value = '';
        }
    });
}

doctorSelect?.addEventListener('change', syncScheduleOptions);
syncScheduleOptions();
