@push('scripts')
<script>
document.querySelectorAll('[data-crud]').forEach(function (modalEl) {
    var form = modalEl.querySelector('form');
    var method = form.querySelector('input[name=_method]');
    var title = modalEl.querySelector('.modal-title');
    var storeUrl = form.getAttribute('action');

    modalEl.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        form.reset();

        if (btn && btn.dataset.item) {
            var data = JSON.parse(btn.dataset.item);
            form.action = btn.dataset.action;
            method.disabled = false;
            title.textContent = btn.dataset.title;
            Object.keys(data).forEach(function (k) {
                var el = form.elements.namedItem(k);
                if (el) el.value = data[k] == null ? '' : data[k];
            });
        } else {
            form.action = storeUrl;
            method.disabled = true;
            title.textContent = modalEl.dataset.titleNew;
        }
    });
});
</script>
@endpush