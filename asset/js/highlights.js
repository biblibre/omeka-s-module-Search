$(document).ready(function() {

    const highligths = $('.highlights');
    highligths.each(function(index, highlight) {
        const fields = $(highlight).find(".highlights_extracts_fieldname");
        const showMoreLinkIcon = $('<a href="#" class="showMoreLink"></a>');
        const maxFieldsCount = 3;

        if (fields.length > maxFieldsCount) {
            $(highlight).append(showMoreLinkIcon);
        }

        const showMoreLink = $(".showMoreLink");
        let showAllFields = false;
        showMoreLink.toggleClass('o-icon-down');
        const quotes = $(highlight).find(".highlights_extracts_quotes");
        const icons = $(highlight).find(".highlights_extracts_view");

        quotes.hide();
        fields.slice(0, maxFieldsCount).toggleClass("showAll");
        icons.slice(maxFieldsCount).addClass('o-icon-view');

        fields.each(function(index, field) {
            const fieldQuotes = $(field).siblings(".highlights_extracts_quotes");
            const icon = $(field).find(".highlights_extracts_view");
            $(icon).on("click", function() {
                fieldQuotes.toggle('show');
                icon.toggleClass('o-icon-view o-icon-private');
            });
        });

        showMoreLink.on("click", function(event) {
            event.preventDefault();

            if (!showAllFields) {
                fields.addClass("showAll");
                showMoreLink.removeClass('o-icon-down');
                showMoreLink.addClass('o-icon-up');
                icons.slice(maxFieldsCount).removeClass('o-icon-private');
                icons.slice(maxFieldsCount).addClass('o-icon-view');
            } else {
                fields.slice(maxFieldsCount).removeClass("showAll");
                quotes.slice(maxFieldsCount).hide();
                icons.slice(maxFieldsCount).toggleClass('o-icon-view o-icon-private');
                showMoreLink.removeClass('o-icon-up');
                showMoreLink.addClass('o-icon-down');
            }
            showAllFields = !showAllFields;
        });
        });
});
