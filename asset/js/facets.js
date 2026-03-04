document.addEventListener('DOMContentLoaded', function () {
    var buttonFilterFacets = document.getElementById('submit-facets');
    if (buttonFilterFacets) {
        buttonFilterFacets.addEventListener('click', function () {
            submitFacets();
        });
    }

    for (const button of document.querySelectorAll('.search-facet .search-facet-expand-button')) {
        button.addEventListener('click', function() {
            this.closest('.search-facet').classList.toggle('search-facet-expanded');
        });
    }

    function submitFacets() {
        var params = new URLSearchParams(window.location.search);

        const facetCheckboxes = Array.from(document.querySelectorAll('.search-facet-item input[type="checkbox"]'));
        const facetNames = new Set(facetCheckboxes.map(node => node.dataset.facetName));
        for (const [key, value] of params.entries()) {
            for (const facetName of facetNames) {
                if (key.startsWith(`limit[${facetName}]`)) {
                    params.delete(key);
                }
            }
        }

        var checkedBoxes = document.querySelectorAll('input[name^="selectedFacets["]:checked');
        checkedBoxes.forEach(box => {
            var name = box.dataset.facetName;
            var value = box.dataset.facetValue;

            params.append(`limit[${name}][]`, value);
        });
        window.location.search = params.toString();
    }
});
