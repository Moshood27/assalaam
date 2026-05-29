# Coop Store & E-Commerce

## Overview
The Coop Store provides a complete e-commerce experience for members to purchase products directly from the cooperative. It supports cash (wallet) purchases and Murabaha (credit/installment) financing.

## Key Features
- **Product Catalog**: Organized by categories with search and sorting.
- **Stock Management**: Track inventory and prevent overselling.
- **Cart & Checkout**: Multi-item cart with persistent storage.
- **Murabaha Financing**: Flexible 6â€“12 month installment plans with profit margin.
- **Order Management**: Admin dashboard to track, fulfill, and manage member orders.
- **Receipts & History**: Detailed order receipts with installment payment tracking.

## Admin Management (Filament)
- **Categories**: Create and manage product categories.
- **Products**: 
  - Define name, description, cost price, and markup percentage.
  - Selling price is automatically calculated: `Cost + (Cost * Markup%)`.
  - Toggle stock tracking and set inventory levels.
- **Orders**:
  - View all member orders and their statuses (Pending, Paid, Completed, Cancelled).
  - Manage Murabaha applications and approve financing.
  - Quick actions to mark orders as Processing, Completed/Delivered, or Cancelled (with automated restock and refund).

## Notifications
- **Push Notifications**: Members receive real-time notifications on their mobile devices when:
  - An order is successfully placed (confirmation).
  - A Murabaha financing application is received.
  - Financing is approved.
  - Order status changes (Processing, Completed, Cancelled).

## Member Storefront (Vue 3)
- **Storefront**: Browse products, filter by category, and search.
- **Product View**: Quick view modal for product details and stock status.
- **Checkout**:
  - **Wallet (Cash)**: Immediate debit from member wallet balance.
  - **Murabaha (Credit)**: Application for financing with chosen tenor (6-12 months).
- **My Orders**: View order history and detailed receipts.
- **Installment Payments**: For credit orders, members can pay installments directly from the receipt view using their wallet balance.

## Technical Details
- **Models**: `Product`, `Category`, `StoreOrder`, `StoreOrderItem`.
- **Controllers**:
  - `ProductController`: Catalog and category listing.
  - `StoreOrderController`: Order placement, stock deduction, and installment payments.
- **Stock Tracking**: Atomic decrement during checkout prevents race conditions.
- **Financing Logic**: Automated calculation of equal monthly installments with remaining balance tracking.
