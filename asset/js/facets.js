document.addEventListener('DOMContentLoaded', function () {
    var buttonFilterFacets = document.getElementById('submit-facets');
    if (buttonFilterFacets) {
        buttonFilterFacets.addEventListener('click', function () {
            submitFacets();
        });
    }

    var buttonToggleHiddenFacets = document.querySelectorAll('.o-icon-down');
    buttonToggleHiddenFacets.forEach(function(button) {
        button.addEventListener('click', function() {
            var hiddenFacets = button.previousElementSibling;
            var facetName = hiddenFacets.getAttribute('data-facet-name');
            toggleHiddenFacets(facetName);
        });
    });

    function toggleHiddenFacets(name) {
        var hiddenFacets = document.querySelector('.hidden-facets[data-facet-name="' + name + '"]');
        var expandButton = document.getElementById('show-hidden-facets');
        if (hiddenFacets.style.display === 'none' || hiddenFacets.style.display === '') {
            hiddenFacets.style.display = 'block';
            expandButton.classList.remove('o-icon-down');
            expandButton.classList.add('o-icon-up');
        } else {
            hiddenFacets.style.display = 'none';
            expandButton.classList.remove('o-icon-up');
            expandButton.classList.add('o-icon-down');
        }
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
