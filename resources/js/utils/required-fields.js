export const markRequiredFields = (scope, labels) => {
    const requiredLabels = new Set(labels);

    document.querySelectorAll(`${scope} label, ${scope} div`).forEach((container) => {
        const label = container.querySelector(':scope > span');

        if (! label || ! requiredLabels.has(label.textContent.trim())) {
            return;
        }

        label.classList.add('required-label');
        container.querySelector('input, textarea, button')?.setAttribute('aria-required', 'true');
    });
};
