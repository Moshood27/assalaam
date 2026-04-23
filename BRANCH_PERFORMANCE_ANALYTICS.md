# Branch Performance Analytics

This document details the Branch Performance Analytics feature, which provides administrators with a visual and data-driven overview of the cooperative’s branch network, focusing on savings mobilization and loan performance (delinquency).

Last updated: 2026‑04‑09

## Overview
The Branch Performance Analytics system consists of a visual map, aggregate performance metrics, and detailed reports. It allows Super Admins to identify high-performing branches and areas requiring intervention due to high default rates.

## Key Performance Indicators (KPIs)
Performance is measured using two primary metrics calculated at the branch level:

1.  **Savings Rate (Total Savings)**:
    *   **Definition**: The sum of all successful contributions where the scheme name is 'Savings' for all members assigned to the branch.
    *   **Logic**: `Branch->getSavingsRateAttribute()`
2.  **Default Rate**:
    *   **Definition**: The percentage of unpaid principal relative to the total principal for all 'Qard Hasan' (benevolent loans) issued to members in the branch.
    *   **Formula**: `((Total Principal - Paid Principal) / Total Principal) * 100`
    *   **Logic**: `Branch->getDefaultRateAttribute()`

## Branch Analytics Map (Admin Panel)
The visual centerpiece is the **Branch Analytics Map**, accessible via the Filament Admin Panel under the **Analytics** navigation group.

### Map Legend
*   **Green Marker**: Low Default Rate (< 10%) - Healthy performance.
*   **Orange Marker**: Medium Default Rate (10% - 20%) - Requires monitoring.
*   **Red Marker**: High Default Rate (> 20%) - Critical intervention needed.
*   **Marker Size**: Reflects the branch's **Total Savings**. Larger markers indicate higher savings mobilization.

### Features
*   **Aggregate Totals**: A header bar shows the total number of branches on the map, the combined total savings, and the average default rate across all mapped branches.
*   **Interactive Markers**: Clicking a branch marker reveals a popup with:
    *   Branch Name
    *   Total Savings (Formatted in ₦)
    *   Default Rate (Percentage)
*   **Auto-scaling**: The map automatically zooms and centers to fit all branches with valid coordinates (latitude/longitude).

**Technical Details**:
*   **Page**: `App\Filament\Pages\BranchPerformanceMap`
*   **View**: `resources/views/filament/pages/branch-performance-map.blade.php`
*   **Library**: [Google Maps JavaScript API](https://developers.google.com/maps/documentation/javascript/overview).

## Branch Performance Report (API)
For integration with external reporting tools or custom dashboards, a JSON API endpoint is available.

*   **Endpoint**: `GET /api/branch-performance`
*   **Middleware**: `auth:sanctum`, `inactivity`, and Admin check.
*   **Response**:
    ```json
    {
      "rows": [
        {
          "branch_id": 1,
          "branch_name": "Main Branch",
          "member_count": 150,
          "total_collections": 5000000.00
        }
      ],
      "totals": {
        "members": 150,
        "collections": 5000000.00
      }
    }
    ```
*   **Controller**: `App\Http\Controllers\Api\AdminReportsController@branchPerformance`

## Data Model
The feature relies on the `Branch` model and its relationships:

*   **Model**: `App\Models\Branch`
*   **Fields**: `id`, `name`, `latitude`, `longitude`, `timestamps`.
*   **Relationships**:
    *   `users()`: Members assigned to the branch.
*   **Computed Attributes**:
    *   `savings_rate`: Sum of successful 'Savings' contributions.
    *   `default_rate`: Calculated percentage of unpaid loan principal.

## Access Control
*   **Admin Panel Map**: Restricted to users with `is_admin === true` or the `super_admin` role.
*   **API Endpoint**: Restricted to authenticated users with `is_admin === true`.
