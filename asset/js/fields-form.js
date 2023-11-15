$(document).ready(function() {

let selectedField;
const sidebarField = $('<div class="sidebar" id="fields-sidebar"></div>');
sidebarField.appendTo('#content');

/**
 * Reset field name select.
 */
const resetFieldNameSelect = function(formElement) {
    const fieldNameSelect = formElement.find('.fields-field-name-select');
    const fieldAddButton = formElement.find('.fields-field-add-button');
    fieldAddButton.prop('disabled', true);
    fieldNameSelect.val('');
    fieldNameSelect.find('option').each(function() {
        const repeatable = this.getAttribute('data-repeatable');
        if (!repeatable) {
            const thisOption = $(this);
            const fieldName = thisOption.val();
            const numFields = formElement.find(`li[data-field-name="${fieldName}"]`).length;
            if (numFields >= 1) {
                thisOption.prop('disabled', true);
            }
        }
    });
};

/**
 * Open the field edit sidebar.
 */
const openSidebarField = function(formElement, field) {
    $.get(formElement.data('fieldEditSidebarUrl'), {
        'field_data': field.data('fieldData')
    }, function(data) {
        sidebarField.html(data);
        Omeka.openSidebar(sidebarField);
    });
};

// Initiate the fields elements on load.
$('.fields-form-element').each(function() {
    const thisFormElement = $(this);
    const fields = thisFormElement.find('.fields-fields');
    // Enable field sorting.
    new Sortable(fields[0], {draggable: '.fields-field', handle: '.sortable-handle'});
    // Add configured fields to list.
    $.get(thisFormElement.data('fieldListUrl'), function(data) {
        thisFormElement.find('.fields-fields').html(data);
        resetFieldNameSelect(thisFormElement);
    });
});

// Handle field name select.
$('.fields-field-name-select').on('change', function(e) {
    const thisSelect = $(this);
    const fieldAddButton = thisSelect.closest('.fields-form-element').find('.fields-field-add-button');
    fieldAddButton.prop('disabled', ('' === thisSelect.val()) ? true : false);
});

// Handle field add button.
$('.fields-field-add-button').on('click', function(e) {
    const thisButton = $(this);
    const formElement = thisButton.closest('.fields-form-element');
    const fieldNameSelect = formElement.find('.fields-field-name-select');
    $.get(formElement.data('fieldRowUrl'), {
        'field_data': {
            'name': fieldNameSelect.val()
        }
    }, function(data) {
        const field = $($.parseHTML(data.trim()));
        formElement.find('.fields-fields').append(field);
        selectedField = field;
        openSidebarField(formElement, field);
        resetFieldNameSelect(formElement);
    });
});

// Handle field edit button.
$(document).on('click', '.fields-field-edit-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const field = thisButton.closest('.fields-field');
    const formElement = thisButton.closest('.fields-form-element');
    selectedField = field;
    openSidebarField(formElement, field);
});

// Handle field remove button.
$(document).on('click', '.fields-field-remove-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const field = thisButton.closest('.fields-field');
    field.addClass('delete');
    field.find('.sortable-handle, .fields-field-label, .fields-field-remove-button, .fields-field-edit-button').hide();
    field.find('.fields-field-restore-button, .fields-field-restore').show();
});

// Handle field restore button.
$(document).on('click', '.fields-field-restore-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const field = thisButton.closest('.fields-field');
    field.removeClass('delete');
    field.find('.sortable-handle, .fields-field-label, .fields-field-remove-button, .fields-field-edit-button').show();
    field.find('.fields-field-restore-button, .fields-field-restore').hide();
});

// Handle field set button.
$(document).on('click', '#fields-field-set-button', function(e) {
    const fieldForm = $('#fields-field-form');
    const formElement = selectedField.closest('.fields-form-element');
    const fieldData = selectedField.data('fieldData');
    let requiredFieldIncomplete = false;
    // Note that we set the value of the input's "data-field-data-key" attribute
    // as the fieldData key and the input's value as its value.
    fieldForm.find(':input[data-field-data-key]').each(function() {
        const thisInput = $(this);
        if (thisInput.prop('required') && '' === thisInput.val()) {
            alert(Omeka.jsTranslate('Required field must be completed'));
            requiredFieldIncomplete = true;
            return false;
        }
        fieldData[thisInput.data('fieldDataKey')] = thisInput.val();
    });
    if (requiredFieldIncomplete) {
        return;
    }
    selectedField.data(fieldData);
    $.get(formElement.data('fieldRowUrl'), {
        'field_data': fieldData
    }, function(data) {
        selectedField.replaceWith(data);
        Omeka.closeSidebar(sidebarField);
    });
});

// Handle form submission.
$(document).on('submit', 'form', function(e) {
    $('.fields-form-element').each(function() {
        const thisFormElement = $(this);
        const fields = thisFormElement.find('.fields-field:not(.delete)');
        const fieldsDataInput = thisFormElement.find('.fields-fields-data');
        const fieldsData = [];
        fields.each(function() {
            const thisField = $(this);
            fieldsData.push(thisField.data('fieldData'));
        });
        fieldsDataInput.val(JSON.stringify(fieldsData));
    });
});

});
