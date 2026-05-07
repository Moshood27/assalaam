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

## 5. Official Governance Groups (Board/Committees)

The system supports the specialized needs of Cooperative leadership:
- **Auto-Discovery**: Board and Committee members see their respective official rooms (e.g., "Board of Directors") automatically in their chat list.
- **Role-Based Joining**: Members can join their designated official rooms with one click; access is restricted via strict `ChatRoomPolicy` checks against Spatie roles.
- **Official Branding**: Official rooms are highlighted with specialized icons (gavel/justice) and badges to distinguish them from general member support chats.
- **immutable Record**: Discussions in these rooms are preserved for regulatory and audit compliance.

## 6. Cooperative Support Chat

The system provides a streamlined way for members to receive assistance:
- **Member-Initiated Inquiries**: Members can start a new "Support Inquiry" directly from the chat dashboard. This creates a dedicated 1-on-1 room between the Member and the Cooperative's Staff.
- **Staff Assignment**: Admins can assign specific support rooms to available staff members to ensure timely responses (Amanah).
- 
- **Canned Responses**: Staff can use pre-approved "Adab-compliant" templates for common questions (e.g., "How to apply for Qard Hasan?"), ensuring consistent and respectful communication.

## 7. Responsive & Mobile Design

- **Mobile Transitions**: Slide-in/out transitions between conversation list and active chat.
- **Auto-Expanding Textarea**: Message input grows as you type, up to a limit, for better readability.
- **Safe Area Support**: Bottom navigation and chat input respect mobile safe areas (iOS/Android).
- **Dashboard Integration**: Quick-access "Chat & Help" cards and back-navigation buttons for seamless flow.

## 8. Real-Time Events

- `ChatMessageSent`: Instant delivery of new messages.
- `ChatMessageUpdated/Deleted`: Syncs edits and soft-deletes across all clients.
- `ChatTyping`: "Brother Ahmed is typing..." indicators.
- `Notification`: Push/In-app alerts for new messages when not in the active room.

## 9. Technical Notes & Maintenance

- **Adab Filter Implementation**: Located in `ChatService.php`, it uses a configurable list of banned words and replaces them with symbols while logging the violation.
- **Message Expiry**: A scheduled command `chat:expire-sensitive-files` should be added to the server crontab to run daily.
- **Real-Time Scaling**: Reverb is configured to handle multiple connections; for high-scale production, ensure the `REVERB_SCALING_ENABLED` env variable is set and Redis is used.
- **Adding New Fintech Actions**: New card types can be added by updating the `msg.type` switch in `IslamicChat.vue` and adding the corresponding metadata structure in `ChatController`.

## 10. Admin & Management (Filament)

The chat system is fully integrated into the Filament Admin Panel for centralized management:
- **Chat Room Management**: Full CRUD capabilities for all room types (Private, Group, Official, Support).
- **Modern Admin Chat**: A rich, Livewire-powered "Modern Chat" interface within the admin panel, allowing staff to communicate in real-time without leaving the dashboard.
- **Member & Staff Assignment**: Dedicated relation managers to manage room participants and assign staff to specific support inquiries.
- **Adab Monitoring**: Filters to quickly identify rooms with flagged messages (profanity detections) for audit and intervention.
- **Governance Tools**: Ability to ban/unban users from the chat system directly from the member list to maintain the etiquette of the cooperative.
- **Chat Analytics**: Real-time statistics on total messages, active rooms, and staff response performance.

---
*Developed for Attaqwa Cooperative - Maintaining the Adab of Finance.*
