/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2017-2018
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

$(document).ready(function() {

    // The form may have more than 1000 fields, so they are jsonified before submit.
    $('#content form').append('<input name="fieldsets" id="fieldsets" value="[]" type="hidden">');
    $('#content form').submit(function(event) {
        event.preventDefault();
        var data = $('#content form').serializeArray();
        var fieldsets = {};
        $.each(data, $.proxy(function(index, element) {
            if (!element) {
                return;
            }
            var posChar = element.name.indexOf('[');
            if (posChar <= 0) {
                return;
            }
            var name = element.name.slice(0, posChar);
            if (fieldsets[name] === undefined) {
                fieldsets[name] = [];
            }
            fieldsets[name].push(element);
            $('input[name="' + element.name + '"]').remove();
            $('select[name="' + element.name + '"]').remove();
        }, this));

        $('#fieldsets').val(JSON.stringify(fieldsets));
        $(this).unbind('submit').submit();
    });

    $('[data-sortable="1"]').each(function() {
        var container = $('<div>').addClass('container');
        $(this).append(container);

        var fieldsets = $(this).children('fieldset');
        fieldsets.hide();

        var availableContainer = $('<div>')
            .addClass('available')
            .append('<h3>' + Omeka.jsTranslate('Available') + '</h3>')
            .append('<div class="sortable"></div>');
        var enabledContainer = $('<div>')
            .addClass('enabled')
            .append('<h3>' + Omeka.jsTranslate('Enabled') + '</h3>')
            .append('<div class="sortable"></div>');
        container
            .append(availableContainer)
            .append(enabledContainer);

        var availableHandles = [];
        var enabledHandles = [];
        var handles = {
            "available": [],
            "enabled": [],
        };
        fieldsets.each(function() {
            var enabled = $(this).find('input[name*="enabled"]:checkbox').prop('checked');

            var handleTitle = $('<span>')
                .html($(this).find('legend').html());

            var handle = $('<div>').append(handleTitle);

            if ($(this).children('fieldset').length > 0) {
                var configButton = $('<a></a>')
                    .attr('href', '#')
                    .html('<i class="fas fa-cog"></i>')
                    .on('click', function(e) {
                        e.preventDefault();
                        var f = $(this).siblings('fieldset').children('fieldset');
                        var dialog = f.clone().dialog({
                            autoOpen: true,
                            modal: true,
                            buttons: {
                                "OK": function() {
                                    dialog.find('input').each(function() {
                                        var name = $(this).attr('name');
                                        var value = $(this).val();
                                        f.find('input[name="' + name + '"]').val(value);
                                    });
                                    dialog.dialog('close');
                                },
                                "Cancel": function() {
                                    dialog.dialog('close');
                                }
                            }
                        });
                    });

                handle.append(configButton);
            }

            handle.append($(this));
            if (enabled) {
                handles.enabled.push(handle);
            } else {
                handles.available.push(handle);
            }

            for (key in handles) {
                handles[key].sort(function(a, b) {
                    var aWeight = a.find('select[name*="weight"]').val();
                    var bWeight = b.find('select[name*="weight"]').val();
                    return aWeight - bWeight;
                });
            }

            availableContainer.find('.sortable').append(handles.available);
            enabledContainer.find('.sortable').append(handles.enabled);
        });

        container.find('.sortable').sortable({
            "connectWith": container.find('.sortable'),
            "update": function(e, ui) {
                var enabled = $.contains(enabledContainer.get(0), ui.item.get(0));
                ui.item.find('input[name*="enabled"]').prop('checked', enabled);

                container.children().each(function() {
                    var i = 0;
                    $(this).find('select[name*="weight"]').each(function() {
                        $(this).val(i++);
                    });
                });
            },
        });

        container.css('display', 'flex');
        container.css('justify-content', 'space-around');
        container.css('align-items', 'flex-start');
        container.children().css('flex-basis', '40%');
    });
});
