import { Link } from 'react-router-dom';

export default function NotFoundPage() {
  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center gap-4 rounded-xl bg-white shadow-card">
      <h2 className="text-3xl font-bold">404</h2>
      <p>Không tìm thấy trang</p>
      <Link to="/" className="rounded bg-primary px-4 py-2 text-white">
        Về trang chủ
      </Link>
    </div>
  );
}
