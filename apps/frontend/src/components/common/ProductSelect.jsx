import { useEffect, useMemo, useRef, useState } from 'react';
import { productsApi } from '../../api/services';

export default function ProductSelect({ value, onChange, placeholder = 'Tìm sản phẩm theo mã hoặc tên', disabled = false }) {
  const [query, setQuery] = useState('');
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [openDropdown, setOpenDropdown] = useState(false);
  const blurTimeoutRef = useRef(null);

  const selectedProductLabel = useMemo(() => {
    if (!value) return '';
    return `${value.product_code} - ${value.name}`;
  }, [value]);

  useEffect(() => {
    if (!query.trim()) {
      setProducts([]);
      return;
    }

    const timer = setTimeout(async () => {
      setLoading(true);
      try {
        const res = await productsApi.list({ per_page: 10, search: query });
        setProducts(res.data.data || []);
      } finally {
        setLoading(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [query]);

  useEffect(() => {
    return () => {
      if (blurTimeoutRef.current) {
        clearTimeout(blurTimeoutRef.current);
      }
    };
  }, []);

  return (
    <div className="relative">
      <input
        className="w-full rounded-lg border px-3 py-2"
        value={query || selectedProductLabel}
        placeholder={placeholder}
        disabled={disabled}
        onFocus={() => setOpenDropdown(true)}
        onChange={(e) => {
          setQuery(e.target.value);
          onChange?.(null);
          setOpenDropdown(true);
        }}
        onBlur={() => {
          blurTimeoutRef.current = setTimeout(() => setOpenDropdown(false), 150);
        }}
      />

      {openDropdown && (
        <div className="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border bg-white shadow-lg">
          {loading && <div className="px-3 py-2 text-sm text-slate-500">Đang tìm kiếm...</div>}
          {!loading && products.length === 0 && <div className="px-3 py-2 text-sm text-slate-500">Không tìm thấy sản phẩm</div>}
          {!loading &&
            products.map((product) => (
              <button
                key={product.id}
                type="button"
                className="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50"
                onMouseDown={() => {
                  setQuery('');
                  onChange?.(product);
                  setOpenDropdown(false);
                }}
              >
                <div className="font-medium">{product.product_code} - {product.name}</div>
                <div className="text-xs text-slate-500">Giá nhập: {Number(product.cost_price || 0).toLocaleString('vi-VN')} đ</div>
              </button>
            ))}
        </div>
      )}
    </div>
  );
}
