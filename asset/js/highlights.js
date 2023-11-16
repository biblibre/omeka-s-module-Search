document.addEventListener('DOMContentLoaded', function () {
    const selector = '.search-highlights-show-more, .search-highlights-show-less';
    document.querySelectorAll(selector).forEach(function (toggleLink) {
        toggleLink.addEventListener('click', function (event) {
            event.preventDefault();
            this.closest('.search-highlights-section')?.classList.toggle('search-highlights-section-open');
        });
    });
});
