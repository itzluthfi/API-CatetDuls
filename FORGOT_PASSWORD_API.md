# API Forgot Password - Dokumentasi

## Flow Reset Password dengan Kode Verifikasi 6 Digit

### 1. Request Kode Verifikasi

**Endpoint:** `POST /api/auth/forgot-password`

**Request Body:**

```json
{
    "email": "user@example.com"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Kode verifikasi telah dikirim ke email Anda",
    "data": {
        "email": "user@example.com",
        "expires_in_minutes": 15
    }
}
```

**Error Response (422):**

```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

---

### 2. Verifikasi Kode (Opsional - untuk validasi di UI)

**Endpoint:** `POST /api/auth/verify-reset-code`

**Request Body:**

```json
{
    "email": "user@example.com",
    "code": "123456"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Kode verifikasi valid",
    "data": {
        "email": "user@example.com",
        "verified": true
    }
}
```

**Error Responses:**

**Kode Tidak Valid (400):**

```json
{
    "success": false,
    "message": "Kode verifikasi tidak valid"
}
```

**Kode Kadaluarsa (400):**

```json
{
    "success": false,
    "message": "Kode verifikasi sudah kadaluarsa"
}
```

**Kode Sudah Digunakan (400):**

```json
{
    "success": false,
    "message": "Kode verifikasi sudah digunakan"
}
```

---

### 3. Reset Password

**Endpoint:** `POST /api/auth/reset-password`

**Request Body:**

```json
{
    "email": "user@example.com",
    "code": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Password berhasil direset. Silakan login dengan password baru."
}
```

**Error Responses:**

**Kode Tidak Valid atau Belum Diverifikasi (400):**

```json
{
    "success": false,
    "message": "Kode verifikasi tidak valid atau belum diverifikasi"
}
```

**Kode Kadaluarsa (400):**

```json
{
    "success": false,
    "message": "Kode verifikasi sudah kadaluarsa"
}
```

**Validasi Gagal (422):**

```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "password": ["The password confirmation does not match."]
    }
}
```

---

## Flow Lengkap untuk Mobile App

### Skenario 1: Dengan Verifikasi Terpisah (Recommended)

1. User memasukkan email → Call `POST /api/auth/forgot-password`
2. User menerima email dengan kode 6 digit
3. User memasukkan kode → Call `POST /api/auth/verify-reset-code`
4. Jika valid, tampilkan form password baru
5. User memasukkan password baru → Call `POST /api/auth/reset-password`
6. Redirect ke halaman login

### Skenario 2: Tanpa Verifikasi Terpisah (Simplified)

1. User memasukkan email → Call `POST /api/auth/forgot-password`
2. User menerima email dengan kode 6 digit
3. User memasukkan kode dan password baru sekaligus → Call `POST /api/auth/reset-password`
4. Redirect ke halaman login

---

## Catatan Penting

### Keamanan

-   Kode verifikasi valid selama **15 menit**
-   Kode hanya bisa digunakan **sekali**
-   Setelah reset password, semua token user akan dihapus (force logout dari semua device)
-   Kode lama akan dihapus ketika user request kode baru

### Email Template

-   Email dikirim dengan subject: "Kode Reset Password - CatetDuls"
-   Email berisi kode 6 digit dengan desain yang menarik
-   Email menampilkan informasi expiry time (15 menit)

### Validasi

-   Email harus terdaftar di sistem
-   Kode harus 6 digit
-   Password minimal 8 karakter
-   Password harus dikonfirmasi (password_confirmation)

---

## Testing dengan Postman/Thunder Client

### 1. Test Forgot Password

```
POST http://localhost:8000/api/auth/forgot-password
Content-Type: application/json

{
  "email": "test@example.com"
}
```

### 2. Test Verify Code

```
POST http://localhost:8000/api/auth/verify-reset-code
Content-Type: application/json

{
  "email": "test@example.com",
  "code": "123456"
}
```

### 3. Test Reset Password

```
POST http://localhost:8000/api/auth/reset-password
Content-Type: application/json

{
  "email": "test@example.com",
  "code": "123456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

## Konfigurasi Email (Laravel)

Pastikan file `.env` sudah dikonfigurasi dengan benar untuk pengiriman email:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Catatan:** Untuk Gmail, gunakan App Password, bukan password akun biasa.
