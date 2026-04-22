(function () {
    'use strict';

    function initializeGlossaryForm(block) {
        const searchPageSelect = block.querySelector('[name$="[o\\:data][search_page]"]');
        const facetFieldSelect = block.querySelector('[name$="[o\\:data][page_facet_field]"]');
        const facetFieldHidden = block.querySelector('[name$="[o\\:data][facet_field]"]');

        facetFieldSelect.addEventListener('change', function () {
            facetFieldHidden.value = facetFieldSelect.value.substring(facetFieldSelect.value.indexOf(':') + 1);
        });

        searchPageSelect.addEventListener('change', function () {
            const searchPageId = this.value;
            for (const option of facetFieldSelect.options) {
                const value = option.getAttribute('value');
                if (value && !value.startsWith(`${searchPageId}:`)) {
                    option.disabled = true;
                    option.selected = false;
                    option.style.display = 'none';
                } else {
                    option.disabled = false;
                    option.style.removeProperty('display');
                }
            }

            facetFieldSelect.dispatchEvent(new Event('change'));
        });

        searchPageSelect.dispatchEvent(new Event('change'));
    }

    $(document).on('o:block-added', function (event) {
        const block = event.target;
        if (block.dataset.blockLayout === 'searchGlossary') {
            initializeGlossaryForm(event.target);
        }
    });

    $(document).ready(function () {
        document.querySelectorAll('.block[data-block-layout="searchGlossary"]').forEach(initializeGlossaryForm);
    });

})();
