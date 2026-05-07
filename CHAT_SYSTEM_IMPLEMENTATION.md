# Islamic Cooperative Chat System Implementation

This document details the implementation of the Sharia-compliant, real-time chat system for the Attaqwa Cooperative.

## 1. Core Architecture

The system is built with a **Laravel (Backend)** and **Vue.js (Frontend)** stack, utilizing **Laravel Reverb** for real-time WebSocket communication and **Tailwind CSS** for a responsive, mobile-first UI.

### Key Components:
- **`ChatRoom`**: Supports private (1-on-1) and group (Committee/Board) chats.
- **`ChatMessage`**: Handles text, media, and specialized "Fintech Cards".
- **`ChatService`**: Centralizes business logic for Adab filters, greetings, and status.
- **`IslamicChat.vue`**: The main interactive chat component.
- **`ChatRooms.vue`**: Dashboard/Sidebar for managing multiple conversations.

## 2. Fintech Integration ("Fintech Ready")

Beyond standard messaging, the system includes financial-grade features:
- **Transaction Cards**: Staff can send payment requests that members can pay with one click.
- **Digital Approvals (E-Signature)**: Legal/Loan agreements can be signed within the chat using the "Ikhlas (Agree)" action.
- **KYC Badges**: Verified members display a green checkmark, building trust (Amanah).
- **2FA Sensitivity**: Rooms containing financial data can be marked as "Sensitive," requiring 2FA for access.

## 3. "Adab" & Sharia Compliance

The system ensures the sanctity of the cooperative environment:
- **Automated Greetings**: One-tap buttons for "Assalamu Alaikum," "JazakAllah Khair," etc.
- **Prayer Time Awareness**: Automatically mutes notifications during local prayer times.
- **Friday Jumu'ah Away Messages**: Professional responses for staff during congregational prayers.
- **Profanity & Adab Filter**: Backend filtering to prevent disrespectful language.
- **Gender-Segregated Groups**: Logic to support specific bylaws for Brothers/Ladies only groups.

## 4. Security & Trust (Amanah)

- **Immutable Audit Trail**: All digital signatures and critical messages are logged via Activity Logs.
- **Sensitive File Expiry**: Documents like ID cards auto-expire and are purged after 48 hours.
- **Channel Authorization**: Strict Laravel Echo private channel rules ensure only authorized members/staff can listen to specific rooms.

## 5. Responsive & Mobile Design

- **Mobile Transitions**: Slide-in/out transitions between conversation list and active chat.
- **Auto-Expanding Textarea**: Message input grows as you type, up to a limit, for better readability.
- **Safe Area Support**: Bottom navigation and chat input respect mobile safe areas (iOS/Android).
- **Dashboard Integration**: Quick-access "Chat & Help" cards and back-navigation buttons for seamless flow.

## 6. Real-Time Events

- `ChatMessageSent`: Instant delivery of new messages.
- `ChatMessageUpdated/Deleted`: Syncs edits and soft-deletes across all clients.
- `ChatTyping`: "Brother Ahmed is typing..." indicators.
- `Notification`: Push/In-app alerts for new messages when not in the active room.

## 7. Technical Notes & Maintenance

- **Adab Filter Implementation**: Located in `ChatService.php`, it uses a configurable list of banned words and replaces them with symbols while logging the violation.
- **Message Expiry**: A scheduled command `chat:expire-sensitive-files` should be added to the server crontab to run daily.
- **Real-Time Scaling**: Reverb is configured to handle multiple connections; for high-scale production, ensure the `REVERB_SCALING_ENABLED` env variable is set and Redis is used.
- **Adding New Fintech Actions**: New card types can be added by updating the `msg.type` switch in `IslamicChat.vue` and adding the corresponding metadata structure in `ChatController`.

---
*Developed for Attaqwa Cooperative - Maintaining the Adab of Finance.*
