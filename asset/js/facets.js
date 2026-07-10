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

    function getRenderedFacetValues() {
        const facetsSet = new Set();
        document
            .querySelectorAll('.search-facet-item input[type="checkbox"]')
            .forEach((node) => {
                facetsSet.add(`${node.dataset.facetName}|${node.dataset.facetValue}`);
            });
        return facetsSet;
    }

    function submitFacets() {
        const params = new URLSearchParams(window.location.search);
        const facetsSet = getRenderedFacetValues();

        const newParams = new URLSearchParams();
        for (const [key, value] of params.entries()) {
            if (key === "page") {
                continue;
            }
            const match = key.match(/^limit\[([^\]]+)\]/);
            if (match && facetsSet.has(`${match[1]}|${value}`)) {
                continue;
            }
            newParams.append(key, value);
        }

        document
            .querySelectorAll('input[name^="selectedFacets["]:checked')
            .forEach((box) => {
                newParams.append(
                    `limit[${box.dataset.facetName}][]`,
                    box.dataset.facetValue,
                );
            });

        window.location.search = newParams.toString();
    }

    function resetFacets() {
        const params = new URLSearchParams(window.location.search);

        const newParams = new URLSearchParams();
        for (const [key, value] of params.entries()) {
            if (key === "page" || /^limit\[/.test(key)) {
                continue;
            }
            newParams.append(key, value);
        }

        window.location.search = newParams.toString();
    }
});
