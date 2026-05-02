export default function LoadingSpinner({ fullPage = false }) {
  return (
    <div className={fullPage ? 'min-h-screen flex items-center justify-center bg-slate-100' : 'py-10 flex justify-center'}>
      <div className="h-9 w-9 animate-spin rounded-full border-4 border-primary border-t-transparent" />
    </div>
  );
}
