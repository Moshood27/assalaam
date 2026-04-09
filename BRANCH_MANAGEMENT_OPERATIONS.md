# Branch Management & Operations

This document explains the administrative management of cooperative branches, member assignment, and branch-wide communication tools.

Last updated: 2026‑04‑09

## Overview
Branches are the primary organizational units of the cooperative. Members are assigned to exactly one branch, and branch-level administration includes managing location data, member outreach, and branch-specific login for the mobile app.

## Branch Administration (Filament)
Admins manage branches via the **Branches** resource in the Filament Admin Panel.

### Key Management Features:
*   **CRUD Operations**: Create, update, and delete branches with their official names and geographic coordinates (Latitude/Longitude).
*   **Coordinate Management**: Latitude and Longitude are required for the **Branch Performance Map**.
*   **Member Counting**: The branch list view provides real-time counts of members (users) assigned to each branch.
*   **Financial Summary**: Shows the total savings mobilized by each branch directly in the list table.

### Bulk Communication
A powerful feature of the Branch Resource is the **Bulk Communicate** action. This allows admins to send mass notifications to all members of a specific branch.

*   **Trigger**: From the Branch list or edit page, click "Bulk Communicate".
*   **Channels**:
    *   **SMS**: Sends text messages via the `SmsService`.
    *   **Push Notification**: Sends FCM push notifications via the `PushService`.
*   **Process**: The communication is dispatched as a background job (`SendBulkCommunication`) to handle branches with large member bases without timing out the admin interface.

**Technical Details**:
*   **Resource**: `App\Filament\Resources\BranchResource`
*   **Job**: `App\Jobs\SendBulkCommunication`
*   **Channels**: `sms` and `push`

## Member Assignment
Each member (`User` model) is linked to a branch via the `branch_id` foreign key.

*   **Assignment**: Typically set during member registration or updated via the User Resource in the admin panel.
*   **Impact**: Branch assignment determines which branch's analytics the member contributes to and which bulk communications they receive.

## Mobile App Integration
The mobile app uses branches as a primary entry point for authentication.

### Branch Selection API
*   **Endpoint**: `GET /api/branches`
*   **Purpose**: Returns a sorted list of all branches for the login screen dropdown.
*   **Controller**: `App\Http\Controllers\Api\AuthController@branches`

### Branch-Based Login
Members log in using their **Branch**, **Membership Number**, and **Password**.
*   **Endpoint**: `POST /api/login`
*   **Logic**: The system validates that the membership number exists within the selected branch before checking the password.
*   **Controller**: `App\Http\Controllers\Api\AuthController@login`

## Data Model Relationships
*   **Branch 1 -> N User**: One branch has many users.
*   **User 1 -> N Contribution**: User's branch is used to aggregate savings.
*   **User 1 -> N QardHasan**: User's branch is used to aggregate default rates.

## Permissions
*   `view_any_branch`: View the branch list.
*   `create_branches`: Create or edit branch details.
*   `delete_records`: Delete branch records (Super Admin only).
