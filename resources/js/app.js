// Confirmations : remplace les anciens onsubmit/onclick="return confirm(...)" (bloqués par la CSP).
// Usage : <form data-confirm="Annuler cette facture ?"> ou <button data-confirm="...">
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    const element = event.target instanceof Element ? event.target.closest('[data-confirm]:not(form)') : null;
    if (element && !window.confirm(element.dataset.confirm)) {
        event.preventDefault();
    }
});
