document.addEventListener("DOMContentLoaded", function () {
    var buttonFilterFacets = document.getElementById("submit-facets");
    if (buttonFilterFacets) {
        buttonFilterFacets.addEventListener("click", function () {
            submitFacets();
        });
    }

    var buttonResetFacets = document.getElementById("reset-facets");
    if (buttonResetFacets) {
        buttonResetFacets.addEventListener("click", function () {
            resetFacets();
        });
    }

    document
        .querySelectorAll(".search-facet-toggle-btn")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                this.closest(".search-facet").classList.toggle(
                    "search-facet-expanded",
                );
            });
        });

    function getVisibleFacetNames() {
        return new Set(
            Array.from(
                document.querySelectorAll(
                    '.search-facet-item input[type="checkbox"]',
                ),
            ).map((node) => node.dataset.facetName),
        );
    }

    function isVisibleFacetParam(key, facetNames) {
        for (const facetName of facetNames) {
            if (key.startsWith(`limit[${facetName}]`)) {
                return true;
            }
        }
        return false;
    }

    function submitFacets() {
        var params = new URLSearchParams(window.location.search);
        const facetNames = getVisibleFacetNames();

        for (const [key] of Array.from(params.entries())) {
            if (isVisibleFacetParam(key, facetNames)) {
                params.delete(key);
            }
        }

        params.delete("page");

        var checkedBoxes = document.querySelectorAll(
            'input[name^="selectedFacets["]:checked',
        );
        checkedBoxes.forEach((box) => {
            var name = box.dataset.facetName;
            var value = box.dataset.facetValue;

            params.append(`limit[${name}][]`, value);
        });
        window.location.search = params.toString();
    }

    function resetFacets() {
        var params = new URLSearchParams(window.location.search);
        const facetNames = getVisibleFacetNames();

        for (const [key] of Array.from(params.entries())) {
            if (isVisibleFacetParam(key, facetNames)) {
                params.delete(key);
            }
        }

        params.delete("page");

        window.location.search = params.toString();
    }
});