/* global jQuery, plupload, WPA9 */
(function ($) {
    'use strict';

    $(function () {
        initUploader();
        initSortable();
        initBulkActions();
        initBulkRegen();
        initInlineEdit();
        initRowActions();
        initViewToggle();
        initAlbumMembership();
    });

    function initUploader() {
        var $box = $('.wpa9-uploader');
        if (!$box.length) return;

        var galleryId = parseInt($box.data('gallery-id'), 10);
        var $queue    = $box.find('.wpa9-upload-queue');
        var $pick     = $box.find('.wpa9-pick-files');

        var uploader = new plupload.Uploader({
            runtimes: 'html5,flash,silverlight,html4',
            browse_button: $pick.get(0),
            container: $box.get(0),
            drop_element: $box.get(0),
            url: WPA9.ajaxUrl,
            file_data_name: 'file',
            multi_selection: true,
            multipart: true,
            multipart_params: {
                action: 'wpa9_upload',
                _wpnonce: WPA9.nonce,
                gallery_id: galleryId
            },
            filters: {
                max_file_size: WPA9.maxUploadSize + 'b',
                mime_types: [{ title: 'Images', extensions: 'jpg,jpeg,png,gif,webp,avif' }]
            }
        });

        uploader.init();

        $box.on('dragover', function (e) { e.preventDefault(); $box.addClass('is-dragover'); });
        $box.on('dragleave drop', function () { $box.removeClass('is-dragover'); });

        uploader.bind('FilesAdded', function (up, files) {
            $.each(files, function (i, file) {
                var $row = $(
                    '<li id="q-' + file.id + '">' +
                    '<span class="wpa9-q-name"></span>' +
                    '<span class="wpa9-progress"><span></span></span>' +
                    '<span class="wpa9-q-percent">0%</span>' +
                    '</li>'
                );
                $row.find('.wpa9-q-name').text(file.name);
                $queue.append($row);
            });
            up.start();
        });

        uploader.bind('UploadProgress', function (up, file) {
            var $row = $('#q-' + file.id);
            $row.find('.wpa9-progress > span').css('width', file.percent + '%');
            $row.find('.wpa9-q-percent').text(file.percent + '%');
        });

        uploader.bind('Error', function (up, err) {
            var $row = $('#q-' + (err.file ? err.file.id : ''));
            $row.addClass('wpa9-q-error').find('.wpa9-q-percent').text(err.message || WPA9.i18n.uploadError);
        });

        uploader.bind('FileUploaded', function (up, file, info) {
            var $row = $('#q-' + file.id);
            try {
                var res = JSON.parse(info.response);
                if (res && res.success) {
                    $row.addClass('wpa9-q-done').find('.wpa9-q-percent').text('100%');
                    appendImage(res.data);
                } else {
                    $row.addClass('wpa9-q-error').find('.wpa9-q-percent').text(
                        (res && res.data && res.data.message) || WPA9.i18n.uploadError
                    );
                }
            } catch (e) {
                $row.addClass('wpa9-q-error').find('.wpa9-q-percent').text(WPA9.i18n.uploadError);
            }
        });

        uploader.bind('UploadComplete', function () {
            setTimeout(function () { $queue.empty(); }, 1500);
        });
    }

    function appendImage(data) {
        var $list = $('.wpa9-images');
        if (!$list.length) return;

        $('.wpa9-empty').remove();

        var li =
            '<li class="wpa9-image" data-id="' + data.id + '">' +
                '<label class="wpa9-image__check"><input type="checkbox" class="wpa9-image-select"></label>' +
                '<div class="wpa9-image__thumb"><img src="' + data.thumb_url + '" alt="" loading="lazy"></div>' +
                '<div class="wpa9-image__meta">' +
                    '<input type="text" class="widefat wpa9-field" data-field="caption" placeholder="Caption" value="">' +
                    '<input type="text" class="widefat wpa9-field" data-field="alt_text" placeholder="Alt text" value="">' +
                    '<textarea class="widefat wpa9-field" data-field="description" rows="2" placeholder="Description"></textarea>' +
                    '<p class="wpa9-image__size">' + WPA9.i18n.imageSize +
                        ' <span class="wpa9-image__size-val">' + (data.thumb_width || 0) + ' × ' + (data.thumb_height || 0) + ' px</span></p>' +
                    '<div class="wpa9-image__actions">' +
                        '<a href="' + data.url + '" target="_blank" rel="noopener">View full</a>' +
                        '<button type="button" class="button-link wpa9-regen">Regen thumb</button>' +
                        '<button type="button" class="button-link delete wpa9-delete-image">Delete</button>' +
                        '<span class="wpa9-status" aria-live="polite"></span>' +
                    '</div>' +
                '</div>' +
            '</li>';
        $list.append(li);
    }

    function initSortable() {
        var $list = $('.wpa9-images');
        if (!$list.length || !$.fn.sortable) return;

        $list.sortable({
            items: '.wpa9-image',
            handle: '.wpa9-image__thumb',
            placeholder: 'wpa9-image wpa9-placeholder',
            tolerance: 'pointer',
            forcePlaceholderSize: true,
            update: function () {
                var ids = $list.find('.wpa9-image').map(function () { return $(this).data('id'); }).get();
                $.post(WPA9.ajaxUrl, {
                    action: 'wpa9_reorder',
                    _wpnonce: WPA9.nonce,
                    gallery_id: $list.data('gallery-id'),
                    ids: ids
                });
            }
        });
    }

    function initBulkActions() {
        var $list = $('.wpa9-images');
        if (!$list.length) return;

        $('.wpa9-select-all').on('change', function () {
            var checked = this.checked;
            $list.find('.wpa9-image-select').prop('checked', checked).trigger('change');
        });

        $list.on('change', '.wpa9-image-select', function () {
            $(this).closest('.wpa9-image').toggleClass('is-selected', this.checked);
        });

        $('.wpa9-bulk-delete').on('click', function () {
            var ids = $list.find('.wpa9-image-select:checked').map(function () {
                return $(this).closest('.wpa9-image').data('id');
            }).get();
            if (!ids.length) {
                window.alert(WPA9.i18n.noSelection);
                return;
            }
            if (!window.confirm(WPA9.i18n.confirmBulkDelete)) return;

            $.post(WPA9.ajaxUrl, {
                action: 'wpa9_bulk_delete',
                _wpnonce: WPA9.nonce,
                ids: ids
            }).done(function () {
                ids.forEach(function (id) {
                    $list.find('.wpa9-image[data-id="' + id + '"]').remove();
                });
                $('.wpa9-select-all').prop('checked', false);
            });
        });
    }

    function initBulkRegen() {
        var $list = $('.wpa9-images');
        if (!$list.length) return;

        var $btn      = $('.wpa9-bulk-regen');
        var $progress = $('.wpa9-regen-progress');
        var $bar      = $progress.find('.wpa9-regen-progress__bar > span');
        var $label    = $progress.find('.wpa9-regen-progress__label');
        var $close    = $progress.find('.wpa9-regen-progress__close');

        $close.on('click', function () {
            $progress.prop('hidden', true).removeClass('is-done is-error is-running');
            $bar.css('width', '0%');
        });

        $btn.on('click', function () {
            if ($btn.prop('disabled')) return;

            var ids = $list.find('.wpa9-image-select:checked').map(function () {
                return $(this).closest('.wpa9-image').data('id');
            }).get();
            if (!ids.length) {
                window.alert(WPA9.i18n.noSelection);
                return;
            }
            if (!window.confirm(WPA9.i18n.confirmBulkRegen)) return;

            var total  = ids.length;
            var done   = 0;
            var failed = 0;

            $btn.prop('disabled', true);
            $('.wpa9-bulk-delete').prop('disabled', true);
            $close.prop('hidden', true);
            $progress.removeClass('is-done is-error').addClass('is-running').prop('hidden', false);
            updateProgress();

            function updateProgress() {
                var pct = Math.round((done / total) * 100);
                $bar.css('width', pct + '%');
                $label.text(WPA9.i18n.regenProgress
                    .replace('%1$d', done)
                    .replace('%2$d', total));
            }

            function finish() {
                $btn.prop('disabled', false);
                $('.wpa9-bulk-delete').prop('disabled', false);
                $bar.css('width', '100%');
                $progress.removeClass('is-running');

                if (failed) {
                    $progress.addClass('is-error');
                    $label.text(WPA9.i18n.regenDoneErrors
                        .replace('%1$d', done - failed)
                        .replace('%2$d', failed));
                } else {
                    $progress.addClass('is-done');
                    $label.text(WPA9.i18n.regenDone.replace('%d', done));
                }

                // Keep the result message on screen; let the user dismiss it.
                $close.prop('hidden', false);

                // Clear the selection now that the work is done.
                $list.find('.wpa9-image-select:checked').prop('checked', false)
                    .closest('.wpa9-image').removeClass('is-selected');
                $('.wpa9-select-all').prop('checked', false);
            }

            // Regenerate sequentially so the server isn't hammered and progress is accurate.
            function next(index) {
                if (index >= total) {
                    finish();
                    return;
                }
                var id    = ids[index];
                var $item = $list.find('.wpa9-image[data-id="' + id + '"]');

                $.post(WPA9.ajaxUrl, {
                    action: 'wpa9_regen_thumb',
                    _wpnonce: WPA9.nonce,
                    id: id
                }).done(function (res) {
                    if (res && res.success && res.data && res.data.thumb_url) {
                        $item.find('.wpa9-image__thumb img').attr('src', res.data.thumb_url);
                        setImageSize($item, res.data.thumb_width, res.data.thumb_height);
                    } else {
                        failed++;
                    }
                }).fail(function () {
                    failed++;
                }).always(function () {
                    done++;
                    updateProgress();
                    next(index + 1);
                });
            }

            next(0);
        });
    }

    function initInlineEdit() {
        var $list = $('.wpa9-images');
        if (!$list.length) return;

        var saveTimers = {};

        $list.on('input change', '.wpa9-field', function () {
            var $item = $(this).closest('.wpa9-image');
            var id    = $item.data('id');
            var $st   = $item.find('.wpa9-status');

            clearTimeout(saveTimers[id]);
            $st.text('…');

            saveTimers[id] = setTimeout(function () {
                var payload = {
                    action: 'wpa9_save_meta',
                    _wpnonce: WPA9.nonce,
                    id: id,
                    caption:     $item.find('[data-field="caption"]').val(),
                    alt_text:    $item.find('[data-field="alt_text"]').val(),
                    description: $item.find('[data-field="description"]').val()
                };
                $.post(WPA9.ajaxUrl, payload).done(function () {
                    $st.text(WPA9.i18n.saved);
                    setTimeout(function () { $st.text(''); }, 1500);
                });
            }, 500);
        });
    }

    function initAlbumMembership() {
        var $box = $('.wpa9-membership');
        if (!$box.length || !$.fn.sortable) return;

        var albumId  = parseInt($box.data('album-id'), 10);
        var $pool    = $box.find('[data-role="pool"]');
        var $members = $box.find('[data-role="members"]');
        var $status  = $box.find('.wpa9-membership__status');
        var $countP  = $box.find('.wpa9-mc-pool');
        var $countM  = $box.find('.wpa9-mc-members');

        var saveTimer = null;

        function save() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () {
                var ids = $members.find('.wpa9-membership__item').map(function () {
                    return parseInt($(this).data('id'), 10);
                }).get();

                $status.removeClass('is-error').text(WPA9.i18n.saving || 'Saving…');

                $.post(WPA9.ajaxUrl, {
                    action: 'wpa9_album_set_galleries',
                    _wpnonce: WPA9.nonce,
                    album_id: albumId,
                    gallery_ids: ids
                }).done(function (res) {
                    if (res && res.success) {
                        $status.text(WPA9.i18n.saved);
                        setTimeout(function () { $status.text(''); }, 1200);
                    } else {
                        $status.addClass('is-error').text((res && res.data && res.data.message) || WPA9.i18n.saveError);
                    }
                }).fail(function () {
                    $status.addClass('is-error').text(WPA9.i18n.saveError);
                });
            }, 250);
        }

        function refreshCounts() {
            $countP.text($pool.find('.wpa9-membership__item').length);
            $countM.text($members.find('.wpa9-membership__item').length);
        }

        $pool.add($members).sortable({
            connectWith: '.wpa9-membership__list',
            items: '.wpa9-membership__item',
            placeholder: 'wpa9-membership__item wpa9-membership__placeholder',
            forcePlaceholderSize: true,
            tolerance: 'pointer',
            cursor: 'grabbing',
            // Fires on the receiving list and on a within-list reorder.
            update: function (event, ui) {
                // Only save once when a cross-list drop occurs (update fires on receiver).
                if (this === ui.item.parent()[0]) {
                    refreshCounts();
                    save();
                }
            }
        }).disableSelection();
    }

    function initViewToggle() {
        var $list    = $('.wpa9-images');
        var $buttons = $('.wpa9-view-mode');
        if (!$list.length || !$buttons.length) return;

        var STORAGE_KEY = 'wpa9_view_mode';
        var initial = 'grid';
        try {
            var stored = window.localStorage && window.localStorage.getItem(STORAGE_KEY);
            if (stored === 'list' || stored === 'grid') initial = stored;
        } catch (e) { /* localStorage unavailable */ }

        apply(initial);

        $buttons.on('click', function () {
            var mode = $(this).data('mode') === 'list' ? 'list' : 'grid';
            apply(mode);
            try {
                if (window.localStorage) window.localStorage.setItem(STORAGE_KEY, mode);
            } catch (e) { /* ignore */ }
        });

        function apply(mode) {
            $list.toggleClass('is-list', mode === 'list');
            $buttons.each(function () {
                var isActive = $(this).data('mode') === mode;
                $(this).toggleClass('is-active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
            });
        }
    }

    function initRowActions() {
        var $list = $('.wpa9-images');
        if (!$list.length) return;

        $list.on('click', '.wpa9-delete-image', function () {
            if (!window.confirm(WPA9.i18n.confirmDeleteImage)) return;
            var $item = $(this).closest('.wpa9-image');
            $.post(WPA9.ajaxUrl, {
                action: 'wpa9_delete_image',
                _wpnonce: WPA9.nonce,
                id: $item.data('id')
            }).done(function () {
                $item.remove();
            });
        });

        $list.on('click', '.wpa9-regen', function () {
            var $item = $(this).closest('.wpa9-image');
            var $st   = $item.find('.wpa9-status');
            $st.text('…');
            $.post(WPA9.ajaxUrl, {
                action: 'wpa9_regen_thumb',
                _wpnonce: WPA9.nonce,
                id: $item.data('id')
            }).done(function (res) {
                if (res && res.success && res.data && res.data.thumb_url) {
                    $item.find('.wpa9-image__thumb img').attr('src', res.data.thumb_url);
                    setImageSize($item, res.data.thumb_width, res.data.thumb_height);
                    $st.text(WPA9.i18n.saved);
                    setTimeout(function () { $st.text(''); }, 1500);
                } else {
                    $st.text(WPA9.i18n.uploadError);
                }
            });
        });
    }

    // Update the "Image size" readout for one image row (thumbnail pixel size).
    function setImageSize($item, w, h) {
        if (!w || !h) return;
        $item.find('.wpa9-image__size-val').text(w + ' × ' + h + ' px');
    }
})(jQuery);
