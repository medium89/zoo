<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Админпанель</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 240px;
            --admin-primary: #5d78db;
            --admin-primary-hover: #4f68c5;
            --admin-primary-active: #465db1;
            --bs-primary: var(--admin-primary);
            --bs-primary-rgb: 93, 120, 219;
        }

        /* Единый акцент для действий создания и подтверждения во всей админке. */
        .content .btn-primary {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--admin-primary);
            --bs-btn-border-color: var(--admin-primary);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--admin-primary-hover);
            --bs-btn-hover-border-color: var(--admin-primary-hover);
            --bs-btn-focus-shadow-rgb: 93, 120, 219;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--admin-primary-active);
            --bs-btn-active-border-color: var(--admin-primary-active);
            --bs-btn-disabled-color: #fff;
            --bs-btn-disabled-bg: var(--admin-primary);
            --bs-btn-disabled-border-color: var(--admin-primary);
        }
        .content .btn-outline-primary {
            --bs-btn-color: var(--admin-primary);
            --bs-btn-border-color: var(--admin-primary);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--admin-primary);
            --bs-btn-hover-border-color: var(--admin-primary);
            --bs-btn-focus-shadow-rgb: 93, 120, 219;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--admin-primary-active);
            --bs-btn-active-border-color: var(--admin-primary-active);
            --bs-btn-disabled-color: var(--admin-primary);
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: var(--admin-primary);
        }

        body {
            min-height: 100vh;
            background: #f7f8fa;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        #sidebarToggle {
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 1100;
            box-shadow: 0 8px 22px rgba(0,0,0,0.12);
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            flex: 0 0 var(--sidebar-width);
            background: #1f232a;
            color: #fff;
            min-height: 100vh;
            transition: transform 0.3s ease, width 0.3s ease, opacity 0.2s ease, visibility 0.2s ease;
            position: sticky;
            top: 0;
            left: 0;
            overflow-y: auto;
            padding: 16px 0 24px;
            border-right: 1px solid #2d323a;
            box-shadow: 6px 0 16px rgba(0,0,0,0.08);
        }

        body.sidebar-collapsed .sidebar {
            width: 0;
            flex-basis: 0;
            transform: translateX(-104%);
            opacity: 0;
            visibility: hidden;
            box-shadow: none;
        }

        .sidebar h4 a {
            color: #fff;
            text-decoration: none;
        }

        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: calc(100vh - 72px);
            padding: 4px 0 0;
        }

        .sidebar-content__item {
            display: flex;
            flex-direction: column;
            width: 100%;
            border-bottom: 1px solid #2d323a;
        }

        .sidebar-content__item:last-child {
            border-bottom: 0;
        }

        .sidebar a {
            color: #e9ecef;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            transition: background 0.2s ease, color 0.2s ease, padding-left 0.2s ease;
        }

        .sidebar .text-muted {
            color: #fff !important;
        }

        .sidebar a.active { background: #2b3038; color: #fff; padding-left: 26px; }
        .sidebar a.active:hover { background: #2b3038; color: #fff; padding-left: 26px; }
        .sidebar a:hover { background: #2b3038; color: #fff; padding-left: 20px; }

        .content {
            padding: 32px;
            flex: 1 1 0;
            min-width: 0;
            width: auto;
            overflow-x: hidden;
        }

        .admin-filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 10px;
            margin: 0 0 20px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .admin-filter-bar__search {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: min(100%, 290px);
            flex: 1 1 260px;
            height: 39px;
            padding: 0 11px;
            border: 1px solid #d8e1eb;
            border-radius: 9px;
            background: #fff;
            color: #78879a;
        }

        .admin-filter-bar__search:focus-within { border-color: #6da3dd; box-shadow: 0 0 0 3px rgba(49, 120, 198, .11); }
        .admin-filter-bar__search input { min-width: 0; width: 100%; border: 0; outline: 0; color: #304255; font-size: .88rem; }
        .admin-filter-bar__field { display: grid; gap: 4px; margin: 0; color: #617287; font-size: .72rem; font-weight: 700; }
        .admin-filter-bar__field .form-select { min-width: 150px; height: 39px; border-color: #d8e1eb; font-size: .84rem; }
        .admin-filter-bar__apply, .admin-filter-bar__reset { min-height: 39px; white-space: nowrap; }
        .admin-filter-bar__reset { display: inline-flex; align-items: center; gap: 7px; }
        @media (max-width: 575px) {
            .admin-filter-bar { align-items: stretch; }
            .admin-filter-bar__search, .admin-filter-bar__field, .admin-filter-bar__field .form-select { width: 100%; min-width: 0; }
            .admin-filter-bar__apply, .admin-filter-bar__reset { flex: 1; justify-content: center; }
        }

        .admin-breadcrumbs {
            margin: 0 0 22px;
        }

        .admin-breadcrumbs ol {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 7px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .admin-breadcrumbs li {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #8190a0;
            font-size: .82rem;
            font-weight: 700;
        }

        .admin-breadcrumbs li + li::before {
            color: #b6c0ca;
            content: '/';
            font-weight: 600;
        }

        .admin-breadcrumbs a {
            color: #64778a;
            text-decoration: none;
            transition: color .15s ease;
        }

        .admin-breadcrumbs a:hover { color: #1f3345; }
        .admin-breadcrumbs li[aria-current="page"] { color: #263846; }

        /* Единый спокойный вид табличных списков админки. */
        .admin-entity-list {
            overflow: visible;
            border: 1px solid #e0e8f0;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(40, 66, 92, .045);
        }
        .admin-entity-list__head,
        .admin-entity-list__row {
            display: grid;
            grid-template-columns: var(--entity-cols, minmax(0, 1fr));
            align-items: center;
            gap: 16px;
        }
        .admin-entity-list__head {
            min-height: 46px;
            padding: 0 18px;
            border-bottom: 1px solid #e8eef4;
            color: #75869a;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
        }
        .admin-entity-list__body { display: grid; }
        .admin-entity-list__row {
            min-height: 70px;
            padding: 12px 18px;
            color: #405266;
            font-size: .84rem;
            border-bottom: 1px solid #edf2f6;
        }
        .admin-entity-list__row:last-child { border-bottom: 0; }
        .admin-entity-list__row:hover { background: #fbfdff; }
        .admin-entity-list__row > * { min-width: 0; }
        .admin-entity-list__actions { justify-self: end; }
        .admin-entity-list__actions .d-flex { flex-wrap: nowrap; }
        .admin-actions-menu { position: relative; display: inline-flex; justify-content: flex-end; }
        .admin-actions-menu__toggle { display: grid; width: 34px; height: 34px; padding: 0; place-items: center; border: 0; border-radius: 9px; background: transparent; color: #657a90; font-size: 1.05rem; line-height: 1; }
        .admin-actions-menu__toggle:hover, .admin-actions-menu__toggle[aria-expanded="true"] { background: #eef5fc; color: #246eaf; }
        .admin-actions-menu__toggle:focus-visible { outline: 3px solid rgba(52, 121, 189, .28); outline-offset: 2px; }
        .admin-actions-menu__popup { position: absolute; z-index: 1070; top: calc(100% + 6px); right: 0; display: grid; min-width: 174px; padding: 5px; border: 1px solid #dfe8f1; border-radius: 11px; background: #fff; box-shadow: 0 14px 30px rgba(31, 54, 79, .18); }
        .admin-actions-menu__popup form { margin: 0; }
        .admin-actions-menu__item { display: flex; width: 100%; align-items: center; gap: 9px; padding: 8px 9px; border: 0; border-radius: 7px; background: transparent; color: #42586e; font: inherit; font-size: .79rem; font-weight: 700; line-height: 1.25; text-align: left; text-decoration: none; }
        .admin-actions-menu__item:hover, .admin-actions-menu__item:focus-visible { background: #eef5fc; color: #256cae; }
        .admin-actions-menu__item--danger { color: #c74750; }
        .admin-actions-menu__item--danger:hover, .admin-actions-menu__item--danger:focus-visible { background: #fff0f0; color: #b73b45; }
        .admin-entity-list__primary {
            display: grid;
            min-width: 0;
            gap: 2px;
        }
        .admin-entity-list__primary a,
        .admin-entity-list__primary strong {
            overflow: hidden;
            color: #2d435a;
            font-size: .9rem;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .admin-entity-list__muted { color: #7c8d9f; font-size: .78rem; }
        .admin-entity-list__avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            object-fit: cover;
            background: #eef4fa;
        }
        .admin-entity-list__empty {
            padding: 38px 20px;
            color: #7b8b9b;
            text-align: center;
        }
        .admin-entity-list__footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
            gap: 12px 18px;
            margin-top: 16px;
            color: #718397;
            font-size: .78rem;
        }
        .admin-entity-list__footer form { display: inline-flex; align-items: center; gap: 8px; margin: 0; }
        .admin-entity-list__footer label { margin: 0; font-weight: 700; }
        .admin-entity-list__footer select { width: auto; min-width: 78px; height: 34px; border-color: #d8e2ec; font-size: .78rem; }
        .admin-entity-list__footer .pagination { margin: 0; }
        .admin-entity-list__footer-pagination { min-width: 0; max-width: 100%; overflow-x: auto; overflow-y: hidden; }
        .admin-entity-list__footer-pagination .pagination { flex-wrap: nowrap; width: max-content; }
        .admin-entity-list__footer-pagination { margin-left: auto; }
        .admin-list-page__actionbar { display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 10px; margin-bottom: 20px; }
        /* Creation moved to the global FAB; empty legacy action rows must not leave a blank gap. */
        .admin-list-page__actionbar:has(> .d-none:only-child),
        .clients-workspace__header:has(> h1.visually-hidden):has(> .d-none) {
            display: none;
        }
        /* List/settings/form pages keep one semantic h1 for screen readers without visual duplication. */
        #admin-content[data-admin-hide-page-heading="1"] > h1,
        #admin-content[data-admin-hide-page-heading="1"] > * > h1:first-child,
        #admin-content[data-admin-hide-page-heading="1"] > * > header h1:first-child {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        @media (max-width: 1199.98px) {
            .admin-entity-list__head { display: none; }
            .admin-entity-list { border: 0; background: transparent; box-shadow: none; }
            .admin-entity-list__body { gap: 10px; }
            .admin-entity-list__row {
                grid-template-columns: var(--entity-cols-mobile, minmax(0, 1fr));
                min-height: 0;
                padding: 14px;
                border: 1px solid #e0e8f0;
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 6px 16px rgba(40, 66, 92, .04);
            }
            .admin-entity-list__row > [data-label]::before {
                display: block;
                margin-bottom: 3px;
                color: #8191a2;
                content: attr(data-label);
                font-size: .67rem;
                font-weight: 800;
                letter-spacing: .02em;
                text-transform: uppercase;
            }
            .admin-entity-list__actions { justify-self: stretch; }
            .admin-entity-list__footer { margin-top: 14px; }
        }
        @media (max-width: 575.98px) {
            .admin-entity-list__row { grid-template-columns: 1fr; gap: 10px; }
            .admin-entity-list__row > [data-label]::before { margin-bottom: 2px; }
            .admin-entity-list__actions .d-flex { justify-content: flex-start; }
            .admin-entity-list__footer { align-items: stretch; flex-direction: column; }
            .admin-entity-list__footer form { justify-content: space-between; }
            .admin-entity-list__footer-pagination { margin-left: 0; }
            .admin-entity-list__footer .pagination { justify-content: center; }
        }

        .admin-to-top {
            position: fixed;
            right: 18px;
            bottom: 18px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #1f232a;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 24px rgba(0,0,0,0.22);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1200;
        }

        .admin-to-top.is-visible {
            opacity: 1;
            visibility: visible;
        }

        .admin-to-top:hover {
            color: #fff;
            transform: translateY(-2px);
        }

        .admin-fab {
            position: fixed;
            right: 22px;
            bottom: calc(22px + env(safe-area-inset-bottom));
            z-index: 1250;
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: var(--admin-primary);
            color: #fff;
            box-shadow: 0 10px 26px rgba(55, 72, 155, .3);
            text-decoration: none;
            cursor: pointer;
            transition: width .18s ease, height .18s ease, transform .18s ease, background .18s ease, opacity .18s ease;
        }
        .admin-fab:hover, .admin-fab:focus-visible {
            width: 62px;
            height: 62px;
            background: var(--admin-primary-hover);
            color: #fff;
            transform: translateY(-3px);
            outline: 0;
        }
        .admin-fab:focus-visible { box-shadow: 0 0 0 4px rgba(93, 120, 219, .28), 0 12px 28px rgba(55, 72, 155, .3); }
        .admin-fab i { font-size: 1.25rem; }
        .admin-fab::after {
            position: absolute;
            right: calc(100% + 10px);
            top: 50%;
            padding: 7px 10px;
            border-radius: 8px;
            background: #202b39;
            color: #fff;
            content: attr(data-tooltip);
            font-size: .78rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-50%) translateX(4px);
            transition: opacity .15s ease, transform .15s ease;
        }
        .admin-fab:hover::after, .admin-fab:focus-visible::after { opacity: 1; transform: translateY(-50%) translateX(0); }
        body.modal-open .admin-fab { opacity: 0; pointer-events: none; }
        .admin-fab--map-animal { right: 88px; }
        .admin-fab--map-client { right: 22px; }

        .sidebar-backdrop {
            display: none;
        }

        /* Нормальные размеры и выравнивание иконок пагинации */
        .pagination svg {
            width: 16px !important;
            height: 16px !important;
            flex-shrink: 0;
        }

        .pagination .page-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.35rem 0.65rem;
        }

        .admin-pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px 18px;
        }

        .admin-pagination .pagination {
            margin: 0;
        }

        .admin-editor-modal .modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 18px 60px rgba(15, 35, 60, .18);
        }

        /* Единый паттерн для форм и подтверждений: как в редакторе заказа. */
        .admin-modal .modal-dialog:not(.modal-lg):not(.modal-xl):not(.modal-sm) {
            --bs-modal-width: 680px;
        }

        .admin-modal .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 22px 60px rgba(28, 45, 64, .22);
            color: #304255;
        }

        .admin-modal .modal-header {
            align-items: flex-start;
            min-height: 74px;
            padding: 19px 24px 17px;
            border-bottom: 1px solid #e7edf3;
        }

        .admin-modal .modal-title {
            color: #2e4054;
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: -.015em;
        }

        .admin-modal .modal-body {
            padding: 20px 24px 22px;
            background: #fff;
            font-size: .9rem;
        }

        .admin-modal .modal-body > .border-top {
            margin-top: 18px;
            padding-top: 18px !important;
            border-color: #e6edf4 !important;
        }

        .admin-modal .form-label {
            margin-bottom: 6px;
            color: #607288;
            font-size: .78rem;
            font-weight: 750;
        }

        .admin-modal .form-control,
        .admin-modal .form-select {
            min-height: 40px;
            border-color: #d8e2ec;
            border-radius: 8px;
            color: #304255;
            font-size: .88rem;
            box-shadow: none;
        }

        .admin-modal textarea.form-control { min-height: 72px; }
        .admin-modal .form-control:focus,
        .admin-modal .form-select:focus { border-color: #6da3dd; box-shadow: 0 0 0 3px rgba(49, 120, 198, .11); }
        .admin-modal .form-text { margin-top: 7px; color: #77889a; font-size: .76rem; line-height: 1.45; }

        .admin-modal .modal-footer {
            gap: 9px;
            padding: 14px 24px 18px;
            border-top: 1px solid #e7edf3;
            background: #fbfcfe;
        }

        .admin-modal .modal-footer .btn { min-height: 39px; padding: 8px 13px; font-size: .84rem; font-weight: 700; }

        @media (max-width: 575px) {
            .admin-modal .modal-dialog { margin: 8px; }
            .admin-modal .modal-header { min-height: 64px; padding: 15px 16px 13px; }
            .admin-modal .modal-title { font-size: 1rem; }
            .admin-modal .modal-body { padding: 16px; }
            .admin-modal .modal-footer { padding: 12px 16px 15px; }
            .admin-modal .modal-footer .btn { flex: 1; padding-right: 8px; padding-left: 8px; }
        }

        .admin-editor-modal .modal-header {
            padding: 17px 20px;
            border-bottom: 1px solid #e8edf3;
        }

        .admin-editor-modal .modal-body {
            padding: 18px 20px 22px;
            background: #fff;
        }

        .admin-editor-modal .modal-dialog { --bs-modal-width: 980px; }
        .admin-editor-modal .modal-title { font-size: 1.05rem; }

        .admin-editor-modal .container-fluid {
            padding: 0;
        }

        .admin-secondary-modal {
            z-index: 1070;
        }

        #confirmUnlinkModal {
            z-index: 1080;
        }

        .client-animal-search-field { position: relative; }
        .client-animal-search-results {
            position: absolute;
            z-index: 1085;
            top: 62px;
            right: 0;
            left: 0;
            display: grid;
            gap: 2px;
            max-height: 210px;
            overflow: auto;
            padding: 6px;
            border: 1px solid #d7e1ec;
            border-radius: 9px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(27, 39, 57, .18);
        }

        .client-animal-search-results.is-hidden { display: none; }
        .client-animal-search-result { padding: 8px 9px; border: 0; border-radius: 6px; background: transparent; color: #34465a; text-align: left; font-size: .86rem; }
        .client-animal-search-result:hover, .client-animal-search-result:focus { background: #edf5ff; color: #1763b7; outline: 0; }
        .client-animal-search-empty { padding: 8px 9px; color: #788699; font-size: .8rem; }
        .address-suggest { position: relative; }
        .address-suggest__results { position: absolute; top: calc(100% + 5px); right: 0; left: 0; z-index: 1095; max-height: 240px; overflow-y: auto; border: 1px solid #d7e2ed; border-radius: 9px; background: #fff; box-shadow: 0 12px 28px rgba(36, 56, 76, .18); }
        .address-suggest__results[hidden] { display: none; }
        .address-suggest__item { display: block; width: 100%; padding: 9px 11px; border: 0; border-bottom: 1px solid #edf1f5; background: #fff; color: #34485b; font-size: .84rem; line-height: 1.35; text-align: left; }
        .address-suggest__item:last-child { border-bottom: 0; }
        .address-suggest__item:hover, .address-suggest__item:focus { background: #edf5ff; color: #1763b7; outline: 0; }
        .address-suggest__empty { padding: 9px 11px; color: #788699; font-size: .8rem; }

        .admin-editor-modal .card {
            box-shadow: none;
        }

        .admin-editor-loading {
            display: grid;
            min-height: 180px;
            place-items: center;
            color: #718096;
        }

        .entity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 7px;
        }

        .entity-tag {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 3px 8px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .entity-tag--positive { color: #237348; background: #e4f6eb; border-color: #bfe8ce; }
        .entity-tag--negative { color: #ae3e3e; background: #fff0f0; border-color: #f2c5c5; }

        .tag-editor__controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .tag-editor__controls .form-control { min-width: 0; }
        .tag-editor__list { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 10px; }
        .tag-editor__item { position: relative; gap: 0; padding: 0; overflow: visible; }
        .tag-editor__label { min-height: 24px; padding: 3px 8px; border: 0; border-radius: inherit; background: transparent; color: inherit; font: inherit; text-align: left; }
        .tag-editor__label:hover { background: rgba(0,0,0,.06); }
        .tag-editor__item.is-classifying { color: #5c6470; background: #f1f3f5; border-color: #d8dde3; }
        .tag-editor__item.is-classifying .tag-editor__label::after { content: ' · ИИ…'; font-weight: 600; }
        .tag-editor__actions { position: absolute; z-index: 5; top: calc(100% + 5px); left: 0; display: flex; gap: 3px; min-width: max-content; padding: 4px; border: 1px solid #dce1e7; border-radius: 9px; background: #fff; box-shadow: 0 8px 20px rgba(31,41,55,.14); }
        .tag-editor__action { border: 0; border-radius: 6px; padding: 4px 7px; background: #f4f6f8; color: #384252; font-size: .72rem; font-weight: 700; }
        .tag-editor__action:hover { background: #e8edf2; }
        .tag-editor__action--remove { color: #ae3e3e; }

        @media (max-width: 575.98px) {
            .admin-editor-modal .modal-body { padding: 16px; }
            .admin-editor-modal .modal-header { padding: 15px 16px; }
            .tag-editor__controls { align-items: stretch; flex-direction: column; }
        }

        /* Шапки страниц и таблицы */
        .content .d-flex.justify-content-between.align-items-center {
            flex-wrap: wrap;
            gap: 12px;
        }

        .content .table img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Псевдо-табличный формат → плитки */
        .content .admin-flex-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 14px;
        }
        .content .admin-flex-table thead {
            display: none;
        }
        .content .admin-flex-table tbody {
            display: block;
        }
        .content .admin-flex-table tr {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px 18px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 10px 24px rgba(31,35,42,0.08);
            margin-bottom: 10px;
        }
        .content .admin-flex-table td {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 0;
            padding: 2px 0;
            flex: 0 1 auto;
        }
        .content .admin-flex-table td::before {
            content: attr(data-label);
            min-width: 140px;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.95rem;
        }
        .content .admin-flex-table td:last-child {
            padding-bottom: 0;
        }
        .content .admin-flex-table td.no-label::before {
            display: none;
        }

        /* История записей: такие же читаемые карточки, как в передержке */
        .content .boarding-history-table.admin-flex-table {
            border-spacing: 0;
        }
        .content .boarding-history-table.admin-flex-table tr {
            display: grid;
            position: relative;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 18px 24px;
            padding: 20px;
            margin-bottom: 14px;
        }
        .content .boarding-history-table.admin-flex-table td {
            display: block;
            min-width: 0;
            padding: 0;
        }
        .content .boarding-history-table.admin-flex-table td::before {
            display: block;
            min-width: 0;
            margin-bottom: 4px;
            color: #7b8794;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .content .boarding-history-table.admin-flex-table td:first-child {
            position: absolute;
            top: 16px;
            right: 18px;
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 3px 9px;
            border: 1px solid #d8dee5;
            border-radius: 999px;
            background: #f8fafc;
            font-size: .82rem;
            font-weight: 700;
        }
        .content .boarding-history-table.admin-flex-table td:first-child::before {
            display: none;
        }
        .content .boarding-history-table.admin-flex-table td:nth-child(2) {
            padding-right: 52px;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .content .boarding-history-table.admin-flex-table td:nth-child(2) a {
            font-weight: 700;
        }
        @media (max-width: 575.98px) {
            .content .boarding-history-table.admin-flex-table tr {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 16px;
            }
        }
        .content .admin-flex-table td .js-order-label,
        .js-order-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            vertical-align: middle;
            min-width: 24px;
        }
        /* Грид-списки (общие) */
        .admin-grid {
            display: grid;
            gap: 12px;
        }
        .admin-grid-header {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px,1fr)));
            gap: 12px;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.95rem;
            padding-left: 2px;
        }
        .admin-grid-body {
            display: grid;
            gap: 12px;
        }
        .admin-grid-row {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px,1fr)));
            gap: 12px;
            padding: 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 8px 20px rgba(31,35,42,0.08);
            align-items: center;
        }
        .admin-grid-row .actions {
            display: flex;
            justify-content: flex-end;
        }
        .text-clip {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @media (max-width: 767.98px) {
            .admin-grid {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }
            .admin-grid-header {
                display: none;
            }
            .admin-grid-body {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .admin-grid-row {
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 14px;
            }
            .admin-grid-row > * {
                display: flex;
                flex-direction: row;
                align-items: flex-start;
                gap: 8px;
                padding: 2px 0;
                width: 100%;
                flex-wrap: wrap;
            }
            .admin-grid-row > *::before {
                content: attr(data-label);
                min-width: 120px;
                font-weight: 600;
                color: #6c757d;
                font-size: 0.95rem;
                line-height: 1.3;
                display: inline-block;
                margin: 0;
                padding-top: 3px;
                flex: 0 0 120px;
            }
            .admin-grid-row > .actions {
                justify-content: flex-start;
            }
            .admin-grid-row .actions > .d-flex {
                justify-content: flex-start;
            }
            .admin-grid-row .text-end {
                text-align: left !important;
            }
            .admin-grid-row img {
                max-width: 160px;
                height: auto;
            }
            .admin-grid-row .js-order-label{
                display: inline-flex;
                align-items: center;
                gap: 6px;
                line-height: 1.2;
            }
            .text-clip {
                -webkit-line-clamp: unset;
                white-space: normal;
            }
        }

        /* Грид-списки */
        .admin-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .admin-grid-table thead {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px, 1fr)));
            gap: 12px;
            padding: 0 0 12px 0;
        }
        .admin-grid-table thead tr {
            display: contents;
        }
        .admin-grid-table thead th {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.95rem;
        }
        .admin-grid-table tbody {
            display: grid;
            gap: 12px;
        }
        .admin-grid-table tbody tr {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px, 1fr)));
            gap: 12px;
            padding: 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 8px 20px rgba(31,35,42,0.08);
            align-items: center;
        }
        .admin-grid-table td {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0;
            border: none;
        }
        .admin-grid-table td.actions {
            justify-content: flex-end;
        }
        .text-clip {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .content .btn {
            white-space: nowrap;
        }

        @media (max-width: 991.98px) {
            :root { --sidebar-width: 280px; }

            .admin-layout {
                display: block;
            }

            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                height: 100vh;
                transform: translateX(-104%);
                opacity: 0;
                visibility: hidden;
                width: var(--sidebar-width);
                flex-basis: var(--sidebar-width);
                box-shadow: 12px 0 26px rgba(0,0,0,0.28);
                z-index: 1040;
            }

            body.sidebar-open .sidebar {
                transform: translateX(0);
                opacity: 1;
                visibility: visible;
            }

            .sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.45);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease;
                z-index: 1030;
            }

            body.sidebar-open .sidebar-backdrop {
                opacity: 1;
                visibility: visible;
            }

            .content {
                padding: 72px 16px 32px;
            }
        }

        @media (max-width: 575.98px) {
            .content {
                padding: 72px 14px 28px;
            }

            .content .btn {
                width: 100%;
            }
        }

        @media (min-width: 992px) {
            #sidebarToggle {
                top: 18px;
                left: 18px;
            }
        }

        /* Каркас админки: узкая панель разделов + контекстная навигация. */
        :root { --admin-rail-width: 72px; --admin-context-width: 260px; }
        .admin-layout { display: flex; min-height: 100vh; overflow: visible; }
        .admin-menu-toggle { display: none; }
        .sidebar {
            display: flex; position: sticky; inset: 0 auto auto 0; z-index: 1020; width: calc(var(--admin-rail-width) + var(--admin-context-width));
            min-height: 100vh; height: 100vh; padding: 0; overflow: hidden; flex: 0 0 auto; color: #dce5f0; background: #18212d;
            border: 0; box-shadow: 6px 0 22px rgba(25, 38, 53, .08); transform: none; opacity: 1; visibility: visible;
        }
        body.sidebar-collapsed .sidebar { width: calc(var(--admin-rail-width) + var(--admin-context-width)); flex-basis: auto; transform: none; opacity: 1; visibility: visible; }
        .admin-brand { display: grid; place-items: center; width: var(--admin-rail-width); height: 72px; color: #fff; text-decoration: none; }
        .admin-brand span { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 11px; background: linear-gradient(135deg, #6f55d9, #9c7ff7); font-size: 1.05rem; font-weight: 800; box-shadow: 0 7px 16px rgba(104, 74, 200, .4); }
        .admin-rail { position: absolute; inset: 72px auto 0 0; display: flex; flex-direction: column; width: var(--admin-rail-width); padding: 16px 0; gap: 7px; background: #18212d; }
        .admin-rail__button { display: grid; place-items: center; width: 48px; height: 48px; margin: 0 auto; padding: 0; border: 0; border-radius: 12px; background: transparent; color: #93a1b3; transition: .18s ease; }
        .admin-rail__button span { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
        .admin-rail__button:hover, .admin-rail__button:focus-visible { color: #fff; background: #253244; outline: 0; }
        .admin-rail__button.is-active { color: #fff; background: #6d58d8; box-shadow: 0 8px 18px rgba(87, 63, 189, .34); }
        .admin-contextual-nav { position: absolute; inset: 0 0 0 var(--admin-rail-width); display: flex; flex-direction: column; width: var(--admin-context-width); padding: 18px 14px 15px; background: #202b39; overflow-y: auto; }
        .admin-sidebar-search { display: flex; align-items: center; gap: 9px; width: 100%; min-height: 42px; margin: 0 0 18px; padding: 8px 11px; border: 1px solid #3a4a5d; border-radius: 10px; background: #293849; color: #d9e3ee; font: inherit; font-size: .84rem; font-weight: 600; text-align: left; }
        .admin-sidebar-search:hover, .admin-sidebar-search:focus-visible { border-color: #9b83ff; background: #2e4053; color: #fff; box-shadow: 0 0 0 3px rgba(155, 131, 255, .16); outline: 0; }
        .admin-sidebar-search span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .admin-contextual-nav__group { display: none; }
        .admin-contextual-nav__group.is-active { display: grid; gap: 3px; }
        .admin-contextual-nav__title { margin: 0 10px 16px; color: #fff; font-size: .95rem; font-weight: 800; }
        .admin-contextual-nav a, .admin-contextual-nav__footer button { display: flex; align-items: center; min-height: 40px; padding: 9px 11px; border: 0; border-radius: 9px; background: transparent; color: #b9c5d3; font-size: .87rem; font-weight: 600; text-decoration: none; text-align: left; }
        .admin-contextual-nav a:hover, .admin-contextual-nav a:focus-visible, .admin-contextual-nav a.active { background: #2d3b4d; color: #fff; outline: 0; }
        .admin-contextual-nav a.active { box-shadow: inset 3px 0 0 #9b83ff; }
        .admin-contextual-nav__footer { margin-top: auto; padding-top: 16px; border-top: 1px solid #344354; }
        .admin-contextual-nav__footer form { margin: 0; }
        .admin-contextual-nav__footer button { width: 100%; gap: 9px; }
        .admin-contextual-nav__footer button:hover, .admin-contextual-nav__footer button:focus-visible { background: #2d3b4d; color: #fff; outline: 0; }
        .content { min-width: 0; padding: 32px; overflow-x: clip; }
        .admin-command-palette kbd { padding: 2px 5px; border: 1px solid #dce3eb; border-radius: 5px; background: #f7f9fb; color: #8490a0; font-family: inherit; font-size: .7rem; }
        .admin-command-palette[hidden] { display: none; }
        .admin-command-palette { position: fixed; inset: 0; z-index: 2000; display: grid; place-items: start center; padding: min(12vh, 120px) 18px 18px; }
        .admin-command-palette__backdrop { position: absolute; inset: 0; background: rgba(19, 29, 42, .42); backdrop-filter: blur(2px); }
        .admin-command-palette__dialog { position: relative; width: min(620px, 100%); overflow: hidden; border: 1px solid #e0e7ef; border-radius: 15px; background: #fff; box-shadow: 0 26px 70px rgba(18, 31, 46, .28); }
        .admin-command-palette__header { display: flex; align-items: center; gap: 11px; padding: 14px 16px; border-bottom: 1px solid #e8edf2; color: #6d58d8; }
        .admin-command-palette__header input { flex: 1; min-width: 0; border: 0; outline: 0; color: #2d4054; font-size: .97rem; }
        .admin-command-palette__results { display: grid; gap: 3px; max-height: min(52vh, 420px); overflow-y: auto; padding: 8px; }
        .admin-command-palette__result { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 11px; border: 0; border-radius: 9px; background: transparent; color: #34465a; text-align: left; font-size: .88rem; font-weight: 650; }
        .admin-command-palette__result span { color: #8492a2; font-size: .75rem; font-weight: 500; }
        .admin-command-palette__result.is-selected, .admin-command-palette__result:hover, .admin-command-palette__result:focus-visible { background: #f0edff; color: #5b46c5; outline: 0; }
        .admin-command-palette__hint { display: flex; gap: 15px; margin: 0; padding: 10px 16px; border-top: 1px solid #e8edf2; color: #8492a2; font-size: .75rem; }
        .admin-command-palette__hint span { display: inline-flex; gap: 3px; align-items: center; }
        @media (max-width: 991.98px) {
            #sidebarToggle.admin-menu-toggle { position: fixed; top: 14px; left: 14px; z-index: 1035; display: grid; place-items: center; width: 42px; height: 42px; padding: 0; border: 0; border-radius: 10px; background: #202b39; color: #fff; box-shadow: 0 8px 20px rgba(31, 45, 63, .2); }
            .sidebar, body.sidebar-collapsed .sidebar { position: fixed; inset: 0 auto 0 0; width: min(338px, calc(100vw - 34px)); height: 100dvh; min-height: 100dvh; transform: translateX(-105%); transition: transform .22s ease; }
            body.sidebar-open .sidebar { transform: translateX(0); }
            .admin-brand { width: var(--admin-rail-width); }
            .admin-rail { inset: 72px auto 0 0; }
            .admin-contextual-nav { width: calc(100% - var(--admin-rail-width)); }
            .sidebar-backdrop { z-index: 1010; }
            .content { padding: 72px 16px 28px; }
        }
        @media (max-width: 575.98px) {
            .content { padding: 70px 14px 24px; }
            .admin-command-palette { padding: 74px 12px 12px; }
            .admin-command-palette__hint { display: none; }
            .admin-fab { right: 16px; bottom: calc(16px + env(safe-area-inset-bottom)); width: 50px; height: 50px; }
            .admin-fab:hover, .admin-fab:focus-visible { width: 56px; height: 56px; }
            .admin-fab::after { right: calc(100% + 8px); font-size: .74rem; }
            .admin-fab--map-animal { right: 78px; }
            .admin-fab--map-client { right: 16px; }
        }

        /* WYSIWYG базовые размеры */
        textarea.wysiwyg,
        textarea.js-wysiwyg,
        textarea.wysiwyg-excerpt { min-height: 220px; resize: vertical; }
        .tox-tinymce { width: 100%; border-radius: 8px; }

        /* Ползунки масштаба/качества */
        .form-range::-webkit-slider-thumb { background: #0d6efd; }
        .form-range::-moz-range-thumb { background: #0d6efd; }
        .form-range::-webkit-slider-runnable-track { background: #dfe3e8; }
        .form-range::-moz-range-track { background: #dfe3e8; }
    </style>
    @vite(['resources/js/app.js'])
    <style>
        /* Общий reset сайта задаёт Inter всем <i>; возвращаем иконкам их шрифт. */
        i.fa, i.fa-classic, i.fa-sharp, i.fas, i.fa-solid, i.far, i.fa-regular {
            font-family: "Font Awesome 6 Free" !important;
            font-style: normal !important;
            font-weight: 900 !important;
        }

        i.far, i.fa-regular {
            font-weight: 400 !important;
        }

        i.fab, i.fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-style: normal !important;
            font-weight: 400 !important;
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $adminNavigation = [
        'work' => ['label' => 'Работа', 'icon' => 'fa-briefcase', 'items' => [
            ['Дашборд', 'admin.dashboard', 'zooadmin/dashboard*'], ['Заказы и работа', 'admin.service-orders.index', 'zooadmin/service-orders'],
            ['Клиенты', 'admin.clients.index', 'zooadmin/clients*'], ['Питомцы', 'admin.animals.index', 'zooadmin/animals*'],
            ['Календарь', 'admin.boarding.index', 'zooadmin/boarding*'], ['Архив заказов', 'admin.service-orders.archive.index', 'zooadmin/service-orders/archive*'],
            ['Карта клиентов', 'admin.client-map.index', 'zooadmin/client-map*'], ['Категории животных', 'admin.categories.index', 'zooadmin/categories*'],
        ]],
        'communication' => ['label' => 'Коммуникации', 'icon' => 'fa-comment-dots', 'items' => [
            ['Обратная связь', 'admin.feedbacks.index', 'zooadmin/feedbacks*'], ['Отзывы Avito', 'admin.avito-reviews.index', 'zooadmin/avito-reviews*'],
        ]],
        'site' => ['label' => 'Сайт', 'icon' => 'fa-wand-magic-sparkles', 'items' => [
            ['Слайдер', 'admin.sliders.index', 'zooadmin/sliders*'], ['Обо мне', 'admin.about.edit', 'zooadmin/about*'],
            ['Преимущества', 'admin.advantages.index', 'zooadmin/advantages*'], ['Услуги', 'admin.services.index', 'zooadmin/services*'],
            ['Фотоальбом', 'admin.galleries.index', 'zooadmin/galleries*'], ['Изображения', 'admin.images.index', 'zooadmin/images*'],
            ['Социальные контакты', 'admin.socials.index', 'zooadmin/socials*'], ['Статьи', 'admin.articles.index', 'zooadmin/articles*'],
            ['Комментарии', 'admin.article-comments.index', 'zooadmin/article-comments*'], ['Меню сайта', 'admin.nav-links.index', 'zooadmin/nav-links*'],
        ]],
        'settings' => ['label' => 'Настройки', 'icon' => 'fa-gear', 'items' => [
            ['Общие настройки', 'admin.settings', 'zooadmin/settings'], ['Telegram-бот', 'admin.telegram-bot-settings.edit', 'zooadmin/settings/telegram-bot*'],
            ['Согласие ПДн', 'admin.personal-data-consent.edit', 'zooadmin/personal-data-consent*'], ['Пользователи', 'admin.users.index', 'zooadmin/users*'],
        ]],
    ];
    $activeNavigationGroup = collect($adminNavigation)->search(fn ($group) => collect($group['items'])->contains(fn ($item) => request()->is($item[2]))) ?: 'work';
@endphp
<button id="sidebarToggle" class="admin-menu-toggle" aria-label="Открыть навигацию" aria-controls="sidebar" aria-expanded="false"><i class="fa fa-bars"></i></button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="admin-layout">
    <nav id="sidebar" class="sidebar" aria-label="Основная навигация">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}" aria-label="Админпанель: главная"><span>Z</span></a>
        <div class="admin-rail" aria-label="Разделы админпанели">
            @foreach($adminNavigation as $key => $group)
                <button type="button" class="admin-rail__button {{ $key === $activeNavigationGroup ? 'is-active' : '' }}" data-admin-group-button="{{ $key }}" aria-controls="admin-group-{{ $key }}" aria-expanded="{{ $key === $activeNavigationGroup ? 'true' : 'false' }}" title="{{ $group['label'] }}">
                    <i class="fa {{ $group['icon'] }}" aria-hidden="true"></i><span>{{ $group['label'] }}</span>
                </button>
            @endforeach
        </div>
        <div class="admin-contextual-nav" aria-label="Навигация текущего раздела">
            <button type="button" class="admin-sidebar-search" id="adminSearchTrigger" aria-haspopup="dialog" aria-controls="adminCommandPalette">
                <i class="fa fa-magnifying-glass" aria-hidden="true"></i><span>Поиск по админке</span>
            </button>
            @foreach($adminNavigation as $key => $group)
                <section id="admin-group-{{ $key }}" class="admin-contextual-nav__group {{ $key === $activeNavigationGroup ? 'is-active' : '' }}" data-admin-group="{{ $key }}" data-admin-group-label="{{ $group['label'] }}" aria-label="{{ $group['label'] }}" aria-hidden="{{ $key === $activeNavigationGroup ? 'false' : 'true' }}">
                    <p class="admin-contextual-nav__title">{{ $group['label'] }}</p>
                    @foreach($group['items'] as [$label, $route, $pattern])
                        <a href="{{ route($route) }}" class="{{ request()->is($pattern) ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </section>
            @endforeach
            <div class="admin-contextual-nav__footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"><i class="fa fa-arrow-right-from-bracket" aria-hidden="true"></i>Выйти</button>
                </form>
            </div>
        </div>
    </nav>
    <main class="content" id="adminMain">
        @php
            $routeName = request()->route()?->getName() ?? '';
            // Keep the dashboard and detail pages' headings; list/form shells use the sidebar label instead.
            $hidePageHeading = $routeName !== 'admin.dashboard'
                && !str_contains($routeName, '.show')
                && !str_contains($routeName, '.tasks');
        @endphp
        <div id="admin-content" data-admin-hide-page-heading="{{ $hidePageHeading ? '1' : '0' }}">
            @yield('content')
        </div>
    </main>
</div>
<div class="admin-command-palette" id="adminCommandPalette" role="dialog" aria-modal="true" aria-labelledby="adminCommandPaletteTitle" hidden>
    <div class="admin-command-palette__backdrop" data-command-close></div>
    <section class="admin-command-palette__dialog">
        <div class="admin-command-palette__header">
            <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
            <label class="visually-hidden" id="adminCommandPaletteTitle" for="adminCommandSearch">Поиск по админке</label>
            <input id="adminCommandSearch" type="search" autocomplete="off" placeholder="Раздел, действие или страница…">
            <kbd>Esc</kbd>
        </div>
        <div class="admin-command-palette__results" id="adminCommandResults" role="listbox" aria-label="Результаты поиска"></div>
        <p class="admin-command-palette__hint"><span><kbd>↑</kbd><kbd>↓</kbd> выбрать</span><span><kbd>Enter</kbd> открыть</span></p>
    </section>
</div>
<div class="modal fade admin-modal admin-editor-modal" id="adminEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminEditorModalTitle">Редактирование</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body" id="adminEditorModalBody">
                <div class="admin-editor-loading">Загрузка формы…</div>
            </div>
        </div>
    </div>
</div>
<a href="#" class="admin-to-top" id="adminToTop" aria-label="Наверх">
    <i class="fas fa-arrow-up"></i>
</a>
<div class="modal fade admin-modal" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Удаление</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="confirmDeleteText">Удалить запись?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade admin-modal" id="confirmUnlinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Отвязать связь?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body"><p class="mb-0" id="confirmUnlinkText">Эта связь будет удалена.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmUnlinkBtn">Отвязать</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const adminContent = document.getElementById('admin-content');
        if (adminContent?.dataset.adminHidePageHeading === '1') {
            const pageHeading = adminContent.querySelector('h1');
            if (pageHeading && !pageHeading.closest('.modal')) {
                pageHeading.classList.add('visually-hidden');
            }
        }
        const sidebar = document.getElementById('sidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');
        const editorModalEl = document.getElementById('adminEditorModal');
        const editorModalBody = document.getElementById('adminEditorModalBody');
        const editorModalTitle = document.getElementById('adminEditorModalTitle');
        const editorModal = editorModalEl ? new bootstrap.Modal(editorModalEl) : null;
        document.querySelectorAll('[data-fab-target]').forEach(fab => fab.addEventListener('click', () => {
            const target = document.querySelector(fab.dataset.fabTarget);
            // Page scripts may attach their handlers in their own DOMContentLoaded callback.
            // Defer one task so the FAB always invokes the fully initialized trigger.
            if (target) window.setTimeout(() => target.click(), 0);
        }));
        const editorScrollKey = `admin-editor-scroll:${window.location.pathname}${window.location.search}`;
        const tagClassificationUrl = @json(route('admin.tags.classify'));
        const yandexMapsApiKey = @json(config('services.yandex.maps_api_key'));
        const yandexSuggestApiKey = @json(config('services.yandex.suggest_api_key'));
        let yandexMapsPromise = null;

        const loadYandexMaps = () => {
            if (window.ymaps) return Promise.resolve(window.ymaps);
            if (!yandexMapsApiKey) return Promise.reject(new Error('Yandex Maps API key is missing'));
            if (yandexMapsPromise) return yandexMapsPromise;

            yandexMapsPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                const suggestParameter = yandexSuggestApiKey ? `&suggest_apikey=${encodeURIComponent(yandexSuggestApiKey)}&load=SuggestView` : '';
                script.src = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(yandexMapsApiKey)}&lang=ru_RU${suggestParameter}`;
                script.async = true;
                script.onload = () => window.ymaps ? resolve(window.ymaps) : reject(new Error('Yandex Maps is unavailable'));
                script.onerror = () => reject(new Error('Yandex Maps failed to load'));
                document.head.append(script);
            });
            return yandexMapsPromise;
        };

        const initAddressSuggest = (root = document) => {
            root.querySelectorAll('input[data-address-suggest], input.form-control[name="address"]').forEach((input) => {
                if (input.dataset.addressSuggestReady === '1') return;
                input.dataset.addressSuggestReady = '1';

                const host = input.parentElement;
                if (!host) return;
                host.classList.add('address-suggest');
                const results = document.createElement('div');
                results.className = 'address-suggest__results';
                results.hidden = true;
                host.append(results);
                let timer = null;

                const close = () => { results.hidden = true; };
                const draw = (items) => {
                    results.replaceChildren();
                    if (!items.length) {
                        const empty = document.createElement('div');
                        empty.className = 'address-suggest__empty';
                        empty.textContent = 'Адрес не найден. Попробуйте указать город, улицу и дом.';
                        results.append(empty);
                    } else {
                        items.forEach((address) => {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'address-suggest__item';
                            item.textContent = address;
                            item.addEventListener('mousedown', (event) => {
                                event.preventDefault();
                                input.value = address;
                                close();
                            });
                            results.append(item);
                        });
                    }
                    results.hidden = false;
                };

                const search = () => {
                    const query = input.value.trim();
                    if (query.length < 3) return close();
                    loadYandexMaps().then((ymaps) => {
                        if (yandexSuggestApiKey && typeof ymaps.suggest === 'function') {
                            return ymaps.suggest(query).then((items) => {
                                draw(items.map((item) => item.value).filter(Boolean));
                            });
                        }
                        return ymaps.geocode(query, {results: 5}).then((response) => {
                            const addresses = [];
                            response.geoObjects.each((geoObject) => {
                                const address = geoObject.properties.get('text');
                                if (address && !addresses.includes(address)) addresses.push(address);
                            });
                            draw(addresses);
                        });
                    }).catch(() => close());
                };

                input.addEventListener('input', () => {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(search, 280);
                });
                input.addEventListener('blur', () => window.setTimeout(close, 160));
            });
        };
        window.initAdminAddressSuggest = initAddressSuggest;

        const restoreEditorScroll = () => {
            const saved = sessionStorage.getItem(editorScrollKey);
            if (!saved) return;
            sessionStorage.removeItem(editorScrollKey);
            requestAnimationFrame(() => window.scrollTo(0, Number(saved) || 0));
        };

        restoreEditorScroll();
        initAddressSuggest();

        const setTagType = (item, type, reason = '') => {
            item.classList.remove('entity-tag--positive', 'entity-tag--negative', 'is-classifying');
            item.classList.add(`entity-tag--${type === 'positive' ? 'positive' : 'negative'}`);
            const typeField = item.querySelector('input[name$="[type]"]');
            if (typeField) typeField.value = type === 'positive' ? 'positive' : 'negative';
            item.title = reason ? `ИИ: ${reason}` : 'Нажмите, чтобы изменить тип или удалить';
        };

        const classifyTag = async (item, name) => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || item.closest('form')?.querySelector('input[name="_token"]')?.value;

            try {
                const response = await fetch(tagClassificationUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    body: JSON.stringify({ tag: name }),
                });
                if (!response.ok) throw new Error('Tag classification failed');

                const result = await response.json();
                setTagType(item, result.type, result.reason || 'Тип определён автоматически.');
            } catch (_) {
                setTagType(item, 'negative', 'Не удалось определить автоматически — проверьте тег вручную.');
            }
        };

        const appendTag = (editor, name) => {
            const cleanName = name.trim().replace(/\s+/g, ' ');
            if (!cleanName) return;

            const list = editor.querySelector('[data-tag-list]');
            const input = editor.querySelector('[data-tag-input]');
            const exists = Array.from(list.querySelectorAll('input[name$="[name]"]'))
                .some((field) => field.value.trim().toLocaleLowerCase('ru') === cleanName.toLocaleLowerCase('ru'));
            if (exists) {
                input.value = '';
                return;
            }

            const index = Number(editor.dataset.tagIndex || list.children.length);
            editor.dataset.tagIndex = String(index + 1);

            const item = document.createElement('span');
            item.className = 'tag-editor__item entity-tag entity-tag--negative is-classifying';
            item.dataset.tagItem = '';
            item.title = 'ИИ определяет тип тега…';
            const label = document.createElement('button');
            label.type = 'button';
            label.className = 'tag-editor__label';
            label.dataset.tagToggle = '';
            label.textContent = cleanName;
            const actions = document.createElement('span');
            actions.className = 'tag-editor__actions';
            actions.dataset.tagActions = '';
            actions.hidden = true;
            [['positive', 'Хороший'], ['negative', 'Проблемный']].forEach(([type, text]) => {
                const action = document.createElement('button');
                action.type = 'button';
                action.className = 'tag-editor__action';
                action.dataset.setTagType = type;
                action.textContent = text;
                actions.appendChild(action);
            });
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'tag-editor__action tag-editor__action--remove';
            remove.dataset.removeTag = '';
            remove.textContent = 'Удалить';
            actions.appendChild(remove);

            const nameField = document.createElement('input');
            nameField.type = 'hidden';
            nameField.name = `tags[${index}][name]`;
            nameField.value = cleanName;
            const typeField = document.createElement('input');
            typeField.type = 'hidden';
            typeField.name = `tags[${index}][type]`;
            typeField.value = 'negative';

            item.append(label, actions, nameField, typeField);
            list.appendChild(item);
            input.value = '';
            classifyTag(item, cleanName);
        };

        document.addEventListener('keydown', (event) => {
            const input = event.target.closest('[data-tag-input]');
            if (!input || event.key !== 'Enter' || event.isComposing) return;
            event.preventDefault();
            appendTag(input.closest('[data-tag-editor]'), input.value);
        });

        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-tag-toggle]');
            if (toggle) {
                const item = toggle.closest('[data-tag-item]');
                const actions = item?.querySelector('[data-tag-actions]');
                if (actions) actions.hidden = !actions.hidden;
                return;
            }

            const setTypeButton = event.target.closest('[data-set-tag-type]');
            if (setTypeButton) {
                const item = setTypeButton.closest('[data-tag-item]');
                setTagType(item, setTypeButton.dataset.setTagType, 'Тип выбран вручную.');
                const actions = item?.querySelector('[data-tag-actions]');
                if (actions) actions.hidden = true;
                return;
            }

            const removeButton = event.target.closest('[data-remove-tag]');
            if (removeButton) removeButton.closest('.tag-editor__item')?.remove();

            if (!event.target.closest('[data-tag-item]')) {
                document.querySelectorAll('[data-tag-actions]').forEach((actions) => { actions.hidden = true; });
            }
        });

        const isEditorUrl = (url) => {
            if (url.origin !== window.location.origin || !url.pathname.startsWith('/zooadmin/')) return false;

            const path = url.pathname.replace(/\/$/, '');
            const standaloneEditors = [
                '/zooadmin/about',
                '/zooadmin/personal-data-consent',
            ];
            const usesFullPageEditor = /^\/zooadmin\/articles(?:\/create|\/[^/]+\/edit)$/.test(path)
                || [
                    '/zooadmin/settings',
                    '/zooadmin/settings/telegram-bot',
                    '/zooadmin/about',
                    '/zooadmin/personal-data-consent',
                ].includes(path);

            if (usesFullPageEditor) return false;

            const isProfile = /^\/zooadmin\/(animals|clients)\/\d+$/.test(path);

            return standaloneEditors.includes(path) || isProfile || /\/create$/.test(path) || /\/edit$/.test(path);
        };

        const setEditorContent = (source) => {
            const heading = source.querySelector('h1, h2, h3');
            editorModalTitle.textContent = heading?.textContent.trim() || 'Редактирование';
            heading?.remove();
            editorModalBody.innerHTML = source.innerHTML;
            initAddressSuggest(editorModalBody);
        };

        const showEditorError = () => {
            editorModalBody.innerHTML = '<div class="alert alert-danger mb-0">Не удалось загрузить форму. Попробуйте ещё раз.</div>';
        };

        const openEditor = async (url) => {
            if (!editorModal || !editorModalBody) {
                window.location.href = url;
                return;
            }

            editorModalTitle.textContent = 'Загрузка…';
            editorModalBody.innerHTML = '<div class="admin-editor-loading">Загрузка формы…</div>';
            editorModal.show();

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = await response.text();
                const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
                const source = documentFromResponse.getElementById('admin-content');

                if (!response.ok || !source) {
                    window.location.href = url;
                    return;
                }

                setEditorContent(source);
            } catch (error) {
                showEditorError();
            }
        };

        const initClientAnimalSearch = (popup) => {
            const input = popup.querySelector('[data-client-animal-search]');
            if (!input || input.dataset.searchReady === '1') return;

            const selectedId = popup.querySelector('#client-existing-animal');
            const newName = popup.querySelector('#client-new-animal');
            const results = popup.querySelector('.client-animal-search-results');
            const details = popup.querySelector('.client-new-animal-details');
            if (!selectedId || !newName || !results) return;

            input.dataset.searchReady = '1';
            let options = [];
            try { options = JSON.parse(input.dataset.animalOptions || '[]'); } catch (_) { options = []; }
            const render = () => {
                const query = input.value.trim().toLowerCase();
                const matches = options.filter((animal) => !query || animal.name.toLowerCase().includes(query)).slice(0, 8);
                results.replaceChildren();
                if (!matches.length) {
                    const empty = document.createElement('div');
                    empty.className = 'client-animal-search-empty';
                    empty.textContent = query ? 'Создать нового питомца с этой кличкой' : 'Сохранённых питомцев пока нет';
                    results.append(empty);
                } else {
                    matches.forEach((animal) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'client-animal-search-result';
                        button.textContent = animal.name + (animal.client ? ' · сейчас у ' + animal.client : '');
                        button.addEventListener('mousedown', (event) => {
                            event.preventDefault();
                            input.value = animal.name;
                            selectedId.value = animal.id;
                            newName.value = '';
                            if (details) details.hidden = true;
                            results.classList.add('is-hidden');
                        });
                        results.append(button);
                    });
                }
                results.classList.remove('is-hidden');
            };

            input.addEventListener('focus', render);
            input.addEventListener('input', () => {
                selectedId.value = '';
                newName.value = input.value.trim();
                if (details) details.hidden = false;
                render();
            });
            input.addEventListener('blur', () => window.setTimeout(() => results.classList.add('is-hidden'), 150));
        };

        const initAnimalClientSearch = (popup) => {
            const input = popup.querySelector('[data-animal-client-search]');
            if (!input || input.dataset.searchReady === '1') return;

            const selectedId = popup.querySelector('#animal-existing-client');
            const newName = popup.querySelector('#animal-new-client-name');
            const results = popup.querySelector('.client-animal-search-results');
            const details = popup.querySelector('.animal-new-client-details');
            if (!selectedId || !newName || !results) return;

            input.dataset.searchReady = '1';
            let options = [];
            try { options = JSON.parse(input.dataset.clientOptions || '[]'); } catch (_) { options = []; }
            const render = () => {
                const query = input.value.trim().toLowerCase();
                const matches = options.filter((client) => !query || client.name.toLowerCase().includes(query)).slice(0, 8);
                results.replaceChildren();
                if (!matches.length) {
                    const empty = document.createElement('div');
                    empty.className = 'client-animal-search-empty';
                    empty.textContent = query ? 'Создать нового клиента с этим именем' : 'Сохранённых клиентов пока нет';
                    results.append(empty);
                } else {
                    matches.forEach((client) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'client-animal-search-result';
                        button.textContent = client.name + (client.phone ? ' · ' + client.phone : '');
                        button.addEventListener('mousedown', (event) => {
                            event.preventDefault();
                            input.value = client.name;
                            selectedId.value = client.id;
                            newName.value = '';
                            if (details) details.hidden = true;
                            results.classList.add('is-hidden');
                        });
                        results.append(button);
                    });
                }
                results.classList.remove('is-hidden');
            };

            input.addEventListener('focus', render);
            input.addEventListener('input', () => {
                selectedId.value = '';
                newName.value = input.value.trim();
                if (details) details.hidden = false;
                render();
            });
            input.addEventListener('blur', () => window.setTimeout(() => results.classList.add('is-hidden'), 150));
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-admin-popup-target]');
            if (!trigger) return;

            const popup = document.querySelector(trigger.dataset.adminPopupTarget);
            if (!popup) return;

            // Карточки клиентов и питомцев загружаются внутрь основной модалки.
            // Вторую Bootstrap-модалку нельзя оставлять вложенной: она окажется
            // под backdrop. Переносим её к body перед открытием.
            if (popup.parentElement !== document.body) {
                document.body.appendChild(popup);
            }
            initClientAnimalSearch(popup);
            initAnimalClientSearch(popup);
            bootstrap.Modal.getOrCreateInstance(popup).show();
        });

        editorModalEl?.addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.admin-secondary-modal').forEach((popup) => popup.remove());
        });

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target === '_blank') return;
            if (!isEditorUrl(new URL(link.href, window.location.origin))) return;

            event.preventDefault();
            openEditor(link.href);
        });

        editorModalBody?.addEventListener('submit', async (event) => {
            const form = event.target.closest('form');
            if (!form) return;

            event.preventDefault();
            const submitButton = form.querySelector('[type="submit"]');
            const originalLabel = submitButton?.innerHTML;
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Сохраняем…';
            }

            try {
                const response = await fetch(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = await response.text();
                const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
                const source = documentFromResponse.getElementById('admin-content');
                const hasValidationErrors = source?.querySelector('.is-invalid, .alert-danger');

                if (!response.ok || !source) {
                    throw new Error('Form request failed');
                }

                if (hasValidationErrors) {
                    setEditorContent(source);
                    return;
                }

                sessionStorage.setItem(editorScrollKey, String(window.scrollY));
                editorModal.hide();
                window.location.reload();
            } catch (error) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger mt-3 mb-0';
                alert.textContent = 'Не удалось сохранить изменения. Проверьте соединение и попробуйте ещё раз.';
                form.appendChild(alert);
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalLabel;
                }
            }
        });

        const isDesktop = () => window.innerWidth >= 992;
        let mobileOpen = false;
        let lastDesktopBreakpoint = isDesktop();
        let activeNavigationGroup = @json($activeNavigationGroup);
        const groupButtons = Array.from(document.querySelectorAll('[data-admin-group-button]'));
        const groupPanels = Array.from(document.querySelectorAll('[data-admin-group]'));
        const drawerBackgroundState = new Map();
        const drawerSemanticState = new Map();
        const drawerBackgroundTargets = () => [
            document.getElementById('adminMain'),
            document.getElementById('adminCommandPalette'),
            document.getElementById('adminToTop'),
        ].filter(Boolean);
        const rememberAttributes = (store, element, attributes) => {
            if (store.has(element)) return;
            store.set(element, Object.fromEntries(attributes.map((attribute) => [attribute, element.getAttribute(attribute)])));
        };
        const restoreAttributes = (store) => {
            store.forEach((attributes, element) => Object.entries(attributes).forEach(([attribute, value]) => {
                if (value === null) element.removeAttribute(attribute);
                else element.setAttribute(attribute, value);
            }));
            store.clear();
        };
        const setDrawerAccessibility = (open) => {
            if (open) {
                rememberAttributes(drawerSemanticState, sidebar, ['role', 'aria-modal', 'aria-label']);
                sidebar?.setAttribute('role', 'dialog');
                sidebar?.setAttribute('aria-modal', 'true');
                sidebar?.setAttribute('aria-label', 'Навигация админпанели');
                drawerBackgroundTargets().forEach((element) => {
                    rememberAttributes(drawerBackgroundState, element, ['inert', 'aria-hidden']);
                    element.setAttribute('inert', '');
                    element.setAttribute('aria-hidden', 'true');
                });
                return;
            }
            restoreAttributes(drawerSemanticState);
            restoreAttributes(drawerBackgroundState);
        };

        const applySidebarState = () => {
            const drawerOpen = !isDesktop() && mobileOpen;
            document.body.classList.toggle('sidebar-open', drawerOpen);
            setDrawerAccessibility(drawerOpen);
            toggleButton?.setAttribute('aria-expanded', String(drawerOpen));
            toggleButton?.setAttribute('aria-label', drawerOpen ? 'Закрыть навигацию' : 'Открыть навигацию');
        };
        const focusableIn = (container) => Array.from(container?.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])') || [])
            .filter((element) => !element.hidden && element.getAttribute('aria-hidden') !== 'true' && !element.closest('[aria-hidden="true"]'));
        const trapFocus = (event, container) => {
            if (event.key !== 'Tab') return false;
            const focusable = focusableIn(container);
            if (!focusable.length) return false;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            } else if (!container.contains(document.activeElement)) {
                event.preventDefault();
                first.focus();
            }
            return true;
        };
        const closeDrawer = (returnFocus = true) => {
            if (!mobileOpen) return;
            mobileOpen = false;
            applySidebarState();
            if (returnFocus) toggleButton?.focus();
        };
        const selectNavigationGroup = (group, focusPanel = false) => {
            activeNavigationGroup = group;
            groupButtons.forEach((button) => {
                const selected = button.dataset.adminGroupButton === group;
                button.classList.toggle('is-active', selected);
                button.setAttribute('aria-expanded', String(selected));
            });
            groupPanels.forEach((panel) => {
                const selected = panel.dataset.adminGroup === group;
                panel.classList.toggle('is-active', selected);
                panel.setAttribute('aria-hidden', String(!selected));
            });
            if (focusPanel) groupPanels.find((panel) => panel.dataset.adminGroup === group)?.querySelector('a')?.focus();
        };

        selectNavigationGroup(activeNavigationGroup);
        applySidebarState();

        toggleButton?.addEventListener('click', () => {
            mobileOpen = !mobileOpen;
            applySidebarState();
            if (mobileOpen) groupButtons.find((button) => button.dataset.adminGroupButton === activeNavigationGroup)?.focus();
        });
        backdrop?.addEventListener('click', () => closeDrawer());
        groupButtons.forEach((button) => button.addEventListener('click', () => selectNavigationGroup(button.dataset.adminGroupButton, !isDesktop())));
        groupPanels.forEach((panel) => panel.addEventListener('click', (event) => {
            if (!isDesktop() && event.target.closest('a')) closeDrawer(false);
        }));
        window.addEventListener('resize', () => {
            const desktop = isDesktop();
            if (desktop !== lastDesktopBreakpoint) {
                if (desktop) mobileOpen = false;
                lastDesktopBreakpoint = desktop;
            }
            applySidebarState();
        });

        const commandPalette = document.getElementById('adminCommandPalette');
        const commandSearch = document.getElementById('adminCommandSearch');
        const commandResults = document.getElementById('adminCommandResults');
        const commandCreateItems = [
            { label: 'Создать заказ', href: @json(route('admin.service-orders.index').'?create=1'), group: 'Быстрое создание' },
            { label: 'Создать клиента', href: @json(route('admin.clients.create')), group: 'Быстрое создание' },
            { label: 'Создать питомца', href: @json(route('admin.animals.create')), group: 'Быстрое создание' },
            { label: 'Создать статью', href: @json(route('admin.articles.create')), group: 'Быстрое создание' },
        ];
        const commandItems = commandCreateItems.concat(groupPanels.flatMap((panel) => Array.from(panel.querySelectorAll('a')).map((link) => ({
            label: link.textContent.trim(), href: link.href, group: panel.dataset.adminGroupLabel || '',
        }))));
        let commandMatches = commandItems;
        let commandIndex = 0;
        let commandLastFocus = null;
        const renderCommandResults = () => {
            if (!commandResults) return;
            const query = (commandSearch?.value || '').trim().toLocaleLowerCase('ru');
            commandMatches = commandItems.filter((item) => `${item.label} ${item.group}`.toLocaleLowerCase('ru').includes(query));
            commandIndex = Math.min(commandIndex, Math.max(commandMatches.length - 1, 0));
            commandResults.replaceChildren();
            if (!commandMatches.length) {
                const empty = document.createElement('p');
                empty.className = 'm-2 text-muted small';
                empty.textContent = 'Ничего не найдено';
                commandResults.append(empty);
                return;
            }
            commandMatches.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `admin-command-palette__result${index === commandIndex ? ' is-selected' : ''}`;
                button.setAttribute('role', 'option');
                button.setAttribute('aria-selected', String(index === commandIndex));
                button.innerHTML = `<span>${item.label}</span><small>${item.group}</small>`;
                button.addEventListener('click', () => { window.location.assign(item.href); });
                commandResults.append(button);
            });
        };
        const closeCommandPalette = () => {
            if (!commandPalette || commandPalette.hidden) return;
            commandPalette.hidden = true;
            commandLastFocus?.focus();
        };
        const openCommandPalette = () => {
            if (!commandPalette || !commandSearch) return;
            if (!commandPalette.hidden) {
                commandSearch.focus();
                return;
            }
            const drawerWasOpen = !isDesktop() && mobileOpen;
            commandLastFocus = drawerWasOpen ? document.getElementById('sidebarToggle') : document.activeElement;
            if (drawerWasOpen) closeDrawer(false);
            commandPalette.hidden = false;
            commandSearch.value = '';
            commandIndex = 0;
            renderCommandResults();
            window.setTimeout(() => commandSearch.focus(), 0);
        };
        document.getElementById('adminSearchTrigger')?.addEventListener('click', openCommandPalette);
        commandPalette?.querySelector('[data-command-close]')?.addEventListener('click', closeCommandPalette);
        commandSearch?.addEventListener('input', () => { commandIndex = 0; renderCommandResults(); });
        document.addEventListener('keydown', (event) => {
            const commandOpen = commandPalette && !commandPalette.hidden;
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openCommandPalette(); return; }
            if (event.key === 'Escape') {
                if (commandOpen) { event.preventDefault(); closeCommandPalette(); return; }
                if (!isDesktop() && mobileOpen) closeDrawer();
                return;
            }
            if (commandOpen && trapFocus(event, commandPalette)) return;
            if (!isDesktop() && mobileOpen && trapFocus(event, sidebar)) return;
            if (!commandOpen) return;
            if (event.key === 'ArrowDown') { event.preventDefault(); commandIndex = Math.min(commandIndex + 1, commandMatches.length - 1); renderCommandResults(); }
            if (event.key === 'ArrowUp') { event.preventDefault(); commandIndex = Math.max(commandIndex - 1, 0); renderCommandResults(); }
            if (event.key === 'Enter' && commandMatches[commandIndex]) { event.preventDefault(); window.location.assign(commandMatches[commandIndex].href); }
        });

        document.querySelectorAll('.content table').forEach((table) => {
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.classList.add('table-responsive', 'admin-table-responsive');
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }

            table.classList.add('admin-flex-table');
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            if (headers.length) {
                table.querySelectorAll('tbody tr').forEach((row) => {
                    Array.from(row.children).forEach((cell, idx) => {
                        if (!cell.dataset.label && headers[idx]) {
                            cell.dataset.label = headers[idx];
                        }
                    });
                });
            }
        });

        document.querySelectorAll('.admin-grid').forEach((grid)=>{
            const headerCells = grid.querySelectorAll(':scope > .admin-grid-header > *');
            if (!headerCells.length) return;
            const labels = Array.from(headerCells).map(cell => cell.textContent.trim());
            grid.querySelectorAll(':scope > .admin-grid-body > .admin-grid-row').forEach((row)=>{
                Array.from(row.children).forEach((cell, idx)=>{
                    if (!cell.dataset.label && labels[idx]) {
                        cell.dataset.label = labels[idx];
                    }
                });
            });
        });

        // Drag & drop сортировка
        if (window.Sortable) {
            document.querySelectorAll('.js-sortable').forEach((el)=>{
                if (el.dataset.customSort === '1') return;
                const sortable = Sortable.create(el, {
                    animation: 150,
                    handle: '.js-order-label',
                    onEnd: updateOrders
                });
                function updateOrders(){
                    const items = el.querySelectorAll('[data-id]')?.length ? el.querySelectorAll('[data-id]') : el.querySelectorAll('tr');
                    items.forEach((row, idx)=>{
                        const orderField = row.querySelector('.js-order-input');
                        const label = row.querySelector('.js-order-label');
                        if (orderField) orderField.value = idx + 1;
                        if (label) label.innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
                    });
                }
                updateOrders();
            });
        }

        // Глобальная замена текста кнопок на иконки
        document.querySelectorAll('button, a').forEach((btn) => {
            if (btn.closest('.modal')) return;
            const txt = (btn.textContent || '').trim();
            if (txt === 'Редактировать' && !btn.matches('.order-actions-menu__item, .admin-actions-menu__item')) {
                btn.innerHTML = '<i class="fa fa-pen"></i>';
                btn.title = 'Редактировать';
                btn.classList.add('btn-icon');
            }
            if (txt === 'Удалить' && !btn.matches('.order-actions-menu__item, .admin-actions-menu__item')) {
                if (btn.hasAttribute('onclick')) btn.removeAttribute('onclick');
                btn.innerHTML = '<i class="fa fa-trash"></i>';
                btn.title = 'Удалить';
                btn.classList.add('btn-icon', 'js-delete-trigger');
                if (!btn.getAttribute('type')) {
                    btn.setAttribute('type','button');
                }
            }
        });

        // Контекстные действия строк и карточек: одно доступное меню вместо набора кнопок.
        document.querySelectorAll('[data-admin-actions-menu]').forEach((menu) => {
            const toggle = menu.querySelector('.admin-actions-menu__toggle');
            const popup = menu.querySelector('.admin-actions-menu__popup');
            if (!toggle || !popup) return;
            const items = () => Array.from(popup.querySelectorAll('a, button')).filter((item) => !item.disabled && item.offsetParent !== null);
            popup.querySelectorAll('a, button').forEach((item) => {
                if (!item.hasAttribute('role')) item.setAttribute('role', 'menuitem');
            });
            const close = () => { popup.hidden = true; toggle.setAttribute('aria-expanded', 'false'); };
            const open = () => {
                popup.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
                requestAnimationFrame(() => items()[0]?.focus());
            };
            toggle.addEventListener('click', (event) => {
                event.stopPropagation();
                const wasOpen = !popup.hidden;
                document.querySelectorAll('[data-admin-actions-menu]').forEach((other) => {
                    if (other !== menu) {
                        const otherToggle = other.querySelector('.admin-actions-menu__toggle');
                        const otherPopup = other.querySelector('.admin-actions-menu__popup');
                        if (otherPopup) otherPopup.hidden = true;
                        otherToggle?.setAttribute('aria-expanded', 'false');
                    }
                });
                wasOpen ? close() : open();
            });
            menu.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') { close(); toggle.focus(); return; }
                if (popup.hidden) return;
                const menuItems = items();
                const current = menuItems.indexOf(document.activeElement);
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    const direction = event.key === 'ArrowDown' ? 1 : -1;
                    menuItems[(current + direction + menuItems.length) % menuItems.length]?.focus();
                }
                if (event.key === 'Home') { event.preventDefault(); menuItems[0]?.focus(); }
                if (event.key === 'End') { event.preventDefault(); menuItems.at(-1)?.focus(); }
            });
        });
        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-admin-actions-menu]')) return;
            document.querySelectorAll('[data-admin-actions-menu] .admin-actions-menu__popup').forEach((popup) => { popup.hidden = true; });
            document.querySelectorAll('[data-admin-actions-menu] .admin-actions-menu__toggle').forEach((toggle) => toggle.setAttribute('aria-expanded', 'false'));
        });

        // Глобальное подтверждение удаления через модалку
        const deleteModalEl = document.getElementById('confirmDeleteModal');
        const deleteTextEl = document.getElementById('confirmDeleteText');
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
        let deleteForm = null;
        document.addEventListener('click', (e)=>{
            const btn = e.target.closest('.js-delete-trigger, form.js-delete-form button[type="submit"]');
            if (!btn || !deleteModal) return;
            if (btn.closest('.modal') && !btn.matches('[data-delete-confirm-modal]')) return;
            const form = btn.closest('form')
                || (btn.dataset.deleteForm ? document.getElementById(btn.dataset.deleteForm) : null)
                || btn.form;
            if (!form) return;
            e.preventDefault();
            deleteForm = form;
            deleteTextEl.textContent = btn.dataset.confirm || form.dataset.confirm || 'Удалить запись?';
            deleteModal.show();
        });
        deleteBtn?.addEventListener('click', ()=>{
            if (deleteForm) {
                deleteForm.submit();
                deleteForm = null;
            }
        });

        const unlinkModalEl = document.getElementById('confirmUnlinkModal');
        const unlinkTextEl = document.getElementById('confirmUnlinkText');
        const unlinkBtn = document.getElementById('confirmUnlinkBtn');
        const unlinkModal = unlinkModalEl ? new bootstrap.Modal(unlinkModalEl) : null;
        let unlinkForm = null;
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.js-unlink-trigger');
            if (!button || !unlinkModal) return;
            const form = button.closest('form');
            if (!form) return;

            event.preventDefault();
            unlinkForm = form;
            unlinkTextEl.textContent = button.dataset.confirm || 'Эта связь будет удалена.';
            unlinkModal.show();
        });
        unlinkBtn?.addEventListener('click', () => {
            if (unlinkForm) {
                unlinkForm.submit();
                unlinkForm = null;
            }
        });

        const toTop = document.getElementById('adminToTop');
        const updateToTopState = () => {
            if (!toTop) return;
            const shouldShow = (window.pageYOffset || document.documentElement.scrollTop) > 320;
            toTop.classList.toggle('is-visible', shouldShow);
        };

        toTop?.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', updateToTopState);
        updateToTopState();
    });
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
