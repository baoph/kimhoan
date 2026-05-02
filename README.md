# KimHoan Monorepo

Monorepo gồm:

- `apps/backend`: Laravel API (REST + Sanctum)
- `apps/frontend`: ReactJS + Vite admin dashboard (style KiotViet)

## Cấu trúc

```bash
apps/
  backend/
  frontend/
```

## Backend setup (Laravel + Sanctum)

```bash
cd apps/backend
cp .env.example .env
composer install
php artisan key:generate
```

### Tạo thư mục cache bắt buộc

Nếu thiếu các thư mục cache/session/view thì Laravel có thể báo lỗi session hoặc CSRF:

```bash
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         bootstrap/cache
```

> Repo đã có sẵn `.gitkeep` trong các thư mục trên để đảm bảo luôn tồn tại khi clone mới.

### Cấu hình `.env` backend quan trọng cho CSRF/Sanctum

Đảm bảo các biến sau đúng (tham khảo từ `.env.example`):

```env
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

Sau đó chạy migrate + seed + serve:

```bash
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

API base: `http://localhost:8000/api/v1`

Tài khoản seed:
- `admin@kimhoan.local` / `12345678`
- `staff@kimhoan.local` / `12345678`

## Frontend setup (React + Vite)

```bash
cd apps/frontend
cp .env.example .env
npm install
npm run dev
```

### Cấu hình `.env` frontend

```env
VITE_API_URL=http://localhost:8000/api/v1
VITE_API_BASE=http://localhost:8000
```

Frontend URL: `http://localhost:5173`

## Luồng đăng nhập SPA với Sanctum (đã áp dụng)

1. Frontend gọi `GET /sanctum/csrf-cookie` (withCredentials=true)
2. Backend set cookie `XSRF-TOKEN` + session cookie
3. Frontend mới gửi `POST /api/v1/auth/login`
4. Các request sau đó dùng cùng credentials/token

## Troubleshooting CSRF token mismatch

Nếu vẫn gặp lỗi `CSRF token mismatch`, kiểm tra lần lượt:

1. **Sai domain/port**
   - Frontend phải chạy từ `localhost:5173` hoặc `127.0.0.1:5173`
   - Backend phải chạy ở `localhost:8000`

2. **Thiếu cookie credentials**
   - Axios instance phải có `withCredentials: true`
   - Request lấy CSRF cookie phải gọi trước login

3. **CORS chưa bật credentials**
   - `config/cors.php` cần:
     - `paths`: `['api/*', 'sanctum/csrf-cookie']`
     - `allowed_origins`: `['http://localhost:5173', 'http://127.0.0.1:5173']`
     - `allowed_headers` và `allowed_methods`: `['*']`
     - `supports_credentials`: `true`

4. **Config cache cũ**
   - Sau khi đổi config/env, chạy:

```bash
cd apps/backend
php artisan optimize:clear
```

5. **Thiếu APP_KEY hoặc session path**
   - Chạy lại `php artisan key:generate`
   - Đảm bảo thư mục `storage/framework/sessions` tồn tại và có quyền ghi

## Tính năng frontend

- Đăng nhập + token storage + protected routes
- Dashboard: thống kê, biểu đồ, top sản phẩm/khách hàng
- Quản lý hàng hóa: list/add/edit/delete + modal + validation
- Quản lý khách hàng: list/add/edit/delete
- Quản lý đơn hàng: list/create/detail/delete
- Báo cáo bán hàng + tồn kho
- Tiếng Việt, responsive, màu chủ đạo `#2563EB`
- Toast notifications

## Stack frontend

- React 19 + Vite
- TailwindCSS
- React Router
- Axios
- Recharts
- React Icons
- React Toastify
- Formik + Yup
- date-fns
