export default function LoadingScreen() {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-white transition-opacity duration-300">
      <div className="text-center">
        <div className="mb-4 inline-block h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600" />
        <p className="animate-pulse text-gray-600">Đang tải...</p>
      </div>
    </div>
  );
}
