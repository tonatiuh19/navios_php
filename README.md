# DockNow 🚢

This is a very basic backend API designed to test store management features in the DockNow React. It provides simple endpoints for user, port, and reservation management, allowing frontend developers to simulate and validate store operations without a full production backend.

---

## 🌟 Features

- **Port Discovery:** Search and filter ports by location, type, and amenities.
- **Detailed Port Info:** View photos, ratings, services, and real-time availability.
- **Reservations:** Book and manage dock reservations with instant confirmation.
- **User Management:** Register, login, and manage user profiles.
- **Secure Payments:** Stripe integration for safe and easy payments.
- **Email Notifications:** Automated emails for confirmations and verifications.
- **Admin Tools:** Manage ports, reservations, and users (API endpoints).

---

## 🛠️ Tech Stack

### Frontend

- **React Native** (mobile app)
- **Expo** (development/build tool)
- **Redux** (state management)
- **Axios** (API requests)

### Backend (This Repo)

- **PHP** (API endpoints)
- **MySQL** (database)
- **PHPMailer** (email sending)
- **Stripe PHP SDK** (payment processing)
- **Twilio PHP SDK** (optional: SMS notifications)

### Infrastructure

- **HostGator** (MySQL hosting)
- **Apache/Nginx** (web server)
- **Composer** (PHP dependency management)

---

## 🚀 Getting Started

### Prerequisites

- PHP >= 7.1
- Composer
- MySQL
- Node.js & npm (for React Native app)
- Expo CLI (for React Native app)

### Backend Setup

1. **Clone the repository:**

   ```sh
   git clone https://github.com/yourusername/docknow-backend.git
   cd docknow-backend
   ```

2. **Install PHP dependencies:**

   ```sh
   composer install
   ```

3. **Configure database:**

   - Edit `/db_cnn/cnn.php` with your MySQL credentials.

4. **Set up environment keys:**

   - Add your Stripe and email credentials to the `navios_environments_keys` and `navios_environments` tables.

5. **Deploy API:**
   - Upload files to your PHP server (e.g., HostGator).
   - Ensure `/vendor` and `/db_cnn` are not publicly accessible.

### Frontend Setup

See the [DockNow React Native app repository](https://github.com/yourusername/docknow-app) for setup instructions.

---

## 🔒 Security

- All sensitive credentials are stored server-side and never exposed to the client.
- Uses prepared statements to prevent SQL injection.
- Stripe and PHPMailer credentials are loaded from the database, not hardcoded.

---

## 📬 Email Templates

- **/code-email.html**: Used for sending verification codes.
- **/confirmation-email.html**: Used for reservation confirmations.
- **/confirmation-class.html**: Used for class confirmations.
- **/event-confirmation-email.html**: Used for event confirmations.

Templates use placeholders like `{{code}}`, `{{name}}`, etc., which are replaced dynamically.
