document.addEventListener('DOMContentLoaded', function () {
    var buttonFilterFacets = document.getElementById('submit-facets');
    if (buttonFilterFacets) {
        buttonFilterFacets.addEventListener('click', function () {
            submitFacets();
        });
    }

    var buttonResetFacets = document.getElementById('reset-facets');
    if (buttonResetFacets) {
        buttonResetFacets.addEventListener('click', function () {
            resetFacets();
        });
    }

    var buttonToggleHiddenFacets = document.querySelectorAll('.show-hidden-facets-btn');
    buttonToggleHiddenFacets.forEach(function(button) {
        button.addEventListener('click', function() {
            var facetName = button.getAttribute('data-facet-name');
            toggleHiddenFacets(facetName, button);
        });
    });

    function toggleHiddenFacets(name, button) {
        var hiddenFacets = document.querySelector('.hidden-facets[data-facet-name="' + name + '"]');
        if (hiddenFacets.style.display === 'none' || hiddenFacets.style.display === '') {
            hiddenFacets.style.display = 'block';
            button.classList.remove('o-icon-down');
            button.classList.add('o-icon-up');
            button.innerHTML = '&nbsp;' + button.getAttribute('data-collapse-label');
        } else {
            hiddenFacets.style.display = 'none';
            button.classList.remove('o-icon-up');
            button.classList.add('o-icon-down');
            button.innerHTML = '&nbsp;' + button.getAttribute('data-expand-label');
        }
    }

    function submitFacets() {
        var checkedBoxes = document.querySelectorAll('input[name^="selectedFacets["]:checked');
        var params = new URLSearchParams(window.location.search);

        checkedBoxes.forEach(box => {
            var name = box.dataset.facetName;
            var value = box.dataset.facetValue;

            params.append(`limit[${name}][]`, value);
        });
        window.location.search = params.toString();
    }

    function resetFacets() {
        var allInputs = document.querySelectorAll('.search-facets input[type="checkbox"]:checked');
        allInputs.forEach(input => {
            input.checked = false;
        });

        var params = new URLSearchParams(window.location.search);

        var newParams = new URLSearchParams();

        for (let [key, value] of params.entries()) {
            if (!key.startsWith('limit[')) {
                newParams.append(key, value);
            }
        }

        window.location.search = newParams.toString();
    }
});