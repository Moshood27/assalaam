<style>
/* Screen defaults: hide print-only header */
.print-only { display: none !important; }

@media print {
  /* Show print-only content */
  .print-only { display: block !important; }

  /* Hide Filament chrome & interactive controls */
  .fi-sidebar,
  .fi-topbar,
  .fi-header,
  .fi-global-search,
  .fi-page-actions,
  .fi-breadcrumbs,
  .fi-tenant-menu,
  .fi-ta-header,
  .fi-ta-filters,
  .fi-ta-bulk-actions,
  .fi-pagination,
  .fi-ta-pagination,
  .fi-ta-header-ctas,
  .fi-fo-actions {
    display: none !important;
  }

  /* Hide selection checkboxes and action columns */
  .fi-ta-table [data-table-column='actions'],
  .fi-ta-table .fi-td-actions,
  .fi-ta-table .fi-actions,
  .fi-ta-table th input[type='checkbox'],
  .fi-ta-table td input[type='checkbox'],
  .fi-action,
  .fi-btn,
  button,
  a[role='button'] {
    display: none !important;
  }

  /* Expand content to full page width */
  body,
  .fi-main,
  .fi-simple-layout,
  .fi-body,
  .fi-page {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: none !important;
    background: #fff !important;
    color: #000 !important;
  }

  /* Make tables printable */
  .fi-ta-table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 12px !important;
  }
  .fi-ta-table thead { display: table-header-group !important; }
  .fi-ta-table th,
  .fi-ta-table td {
    border: 1px solid #000 !important;
    padding: 6px 8px !important;
    color: #000 !important;
  }

  /* Remove UI backgrounds and effects that don't print well */
  * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    box-shadow: none !important;
    background: transparent !important;
  }

  @page {
    size: A4;
    margin: 12mm;
  }
}
</style>
