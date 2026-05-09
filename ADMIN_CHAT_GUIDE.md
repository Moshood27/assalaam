### Admin Chat Guide

This guide explains how Cooperative Representatives (Admins) can use the real-time chat system to communicate with members and vendors using the unified **Chat Rooms** system.

---

### 1. Chatting with Members
There are two primary ways to initiate or continue a chat with a member:

#### A. Via Member List (User Resource)
1. Navigate to **Users** in the sidebar.
2. Find the member you wish to chat with.
3. Click the **Chat** (bubble icon) action in the table.
4. You will be redirected to the unified chat interface for a 1-on-1 private room with that member.

#### B. Via Chat Rooms Management
1. Navigate to **Chat Rooms** in the sidebar.
2. Here you can see all active rooms, including **Support** rooms initiated by members from the mobile app.
3. Support rooms are labeled with a **SUPPORT** badge and include the member's name.
4. Click **Enter Chat** to join the conversation.

---

### 2. Chatting with Vendors
Admins can communicate directly with vendor owners:

#### A. Via Vendor List
1. Navigate to **Vendors** in the sidebar.
2. In the table view, click the **Chat with Owner** action.
3. This opens a private chat room with the **Owner** of that vendor.

---

### 3. Key Features
*   **Real-time Updates**: Messages appear instantly without refreshing the page, powered by Laravel Reverb.
*   **Media Support**: Send and receive images or documents directly in the chat.
*   **Message Management**: Edit or delete messages (soft-delete for audit trail).
*   **Fintech Integration**: Trigger payment requests or approvals directly within the chat for members.
*   **Mobile Friendly**: The interface is optimized for both desktop and mobile devices.

---

### 4. How it Works (Technical)
*   **Unified System**: The system uses `ChatRoom` and `ChatMessage` models for all types of communication (Support, Private, Groups).
*   **Broadcasting**: When a message is sent, a `ChatMessageSent` event is broadcasted on a private channel (`chat.room.{room_id}`).
*   **UI**: The admin panel uses a custom Filament page with a reactive chat interface.
