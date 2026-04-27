### Admin Chat Guide

This guide explains how Cooperative Representatives (Admins) can use the real-time chat system to communicate with members and vendors.

---

### 1. Chatting with Members
There are two primary ways to initiate or continue a chat with a member:

#### A. Via Member Profile (User Resource)
1. Navigate to **Users** in the sidebar.
2. Find the member you wish to chat with and click **Edit**.
3. Scroll down to the tabs section and select the **Support Chat** tab.
4. The chat interface will load, allowing you to see message history and send new messages in real-time.

#### B. Via Support Messages List
1. Navigate to **Support Messages** in the sidebar.
2. You will see a list of all recent messages from members.
3. Click the **Chat** (bubble icon) action next to any message.
4. A modal will open with the full chat history for that specific member.

---

### 2. Chatting with Vendors
Admins can communicate directly with vendor owners:

#### A. Via Vendor List
1. Navigate to **Vendors** in the sidebar.
2. In the table view, click the **Chat** action (bubble icon) for any vendor.
3. This opens a chat modal with the **Owner** of that vendor.

#### B. Via Vendor Edit Page
1. Navigate to **Vendors** and click **Edit** on a specific vendor.
2. Select the **Support Chat** tab.
3. This interface connects you directly to the vendor's owner.

---

### 3. Key Features
*   **Real-time Updates**: Messages appear instantly without refreshing the page, powered by Laravel Reverb.
*   **Read Receipts**: Admins can see if a message has been "Sent" or "Read" (based on when the member opens it in the mobile app).
*   **Automatic Marking**: When an admin opens a chat window, all unread messages from that member are automatically marked as read.
*   **Mobile Friendly**: The chat interface is optimized for use on mobile browsers for reps on the go.

---

### 4. How it Works (Technical)
*   **Livewire**: The `SupportChat` component handles the UI and message sending logic.
*   **Broadcasting**: When a message is sent, a `SupportMessageSent` event is broadcasted on a private channel (`support.{user_id}`).
*   **Echo**: The admin panel listens for these events to refresh the chat window dynamically.
