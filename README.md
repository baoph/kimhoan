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

## Chạy backend

```bash
cd apps/backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

API base: `http://localhost:8000/api/v1`

Tài khoản seed:
- `admin@kimhoan.local` / `12345678`
- `staff@kimhoan.local` / `12345678`

## Chạy frontend

```bash
cd apps/frontend
cp .env.example .env
npm install
npm run dev
```

Frontend URL: `http://localhost:5173`

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
