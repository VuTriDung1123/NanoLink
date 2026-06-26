import { useState } from 'react';
import axios from 'axios';

export default function App() {
  const [originalUrl, setOriginalUrl] = useState('');
  const [customAlias, setCustomAlias] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setResult(null);

    try {
      // Đọc địa chỉ API từ file .env (Nếu chạy local không có .env thì nó dùng mặc định nanolink.local)
      const API_URL = import.meta.env.VITE_API_URL || 'http://nanolink.local';
      
      const response = await axios.post(`${API_URL}/api/shorten`, {
        original_url: originalUrl,
        custom_alias: customAlias || null
      });

      if (response.data.status === 'success') {
        setResult(response.data.data);
      }
    } catch (err) {
      if (err.response && err.response.data) {
        setError(err.response.data.message);
      } else {
        setError('Có lỗi xảy ra khi kết nối đến máy chủ. Chắc server lại hết tiền rồi!');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-sakura-50 flex flex-col font-sans text-gray-800">
      
      {/* ⚠️ BANNER CẢNH BÁO "DEV KIẾM CƠM" */}
      <div className="bg-rose-500 text-white text-sm font-medium py-2 px-4 text-center shadow-md z-50 flex items-center justify-center gap-2">
        <span>⚠️</span>
        <p>
          <strong>Cảnh báo nhẹ:</strong> Đây là dự án "kiếm cơm qua ngày" của một dev mỏng manh. 
          Hệ thống có thể sập bất cứ lúc nào nếu hết tiền duy trì server. Vui lòng cân nhắc khi dùng cho link hệ trọng! 🐟🍚
        </p>
      </div>

      {/* NAVBAR */}
      <header className="w-full max-w-5xl mx-auto px-6 py-4 flex justify-between items-center">
        <div className="flex items-center gap-2 cursor-pointer hover:scale-105 transition-transform">
          <span className="text-3xl">🌸</span>
          <h1 className="text-2xl font-extrabold text-sakura-600 tracking-tight">NanoLink</h1>
        </div>
        <nav className="hidden md:flex gap-6 text-sakura-600 font-medium">
          <a href="#" className="hover:text-rose-500 transition-colors">Bảng giá (0đ)</a>
          <a href="#" className="hover:text-rose-500 transition-colors">API API</a>
          <a href="#" className="hover:text-rose-500 transition-colors">Liên hệ</a>
        </nav>
        <button className="px-5 py-2 bg-white text-sakura-600 font-bold rounded-full shadow-sm hover:shadow-md border border-sakura-200 transition-all">
          Donate cho Dev
        </button>
      </header>

      {/* MAIN HERO SECTION */}
      <main className="flex-1 w-full max-w-5xl mx-auto px-6 py-12 md:py-20 flex flex-col lg:flex-row items-center gap-12">
        
        {/* Left Column: Form */}
        <div className="w-full lg:w-1/2 space-y-8">
          <div>
            <span className="inline-block py-1 px-3 rounded-full bg-sakura-100 text-sakura-600 text-sm font-bold mb-4 border border-sakura-200">
              Miễn phí 100% (Tạm thời thế)
            </span>
            <h2 className="text-4xl md:text-5xl font-extrabold text-gray-900 leading-tight mb-4">
              Rút gọn link <br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-sakura-500 to-rose-500">
                Nhanh & Nguy hiểm
              </span>
            </h2>
            <p className="text-lg text-gray-600">
              Tạo link ngắn, QR Code tức thì. Không cần đăng nhập. Không quảng cáo rác. Chỉ có sự mượt mà.
            </p>
          </div>

          <div className="bg-white p-6 rounded-3xl shadow-xl border border-sakura-100 relative">
            <form onSubmit={handleSubmit} className="space-y-5">
              <div>
                <input
                  type="url"
                  required
                  placeholder="Dán link siêu dài của bạn vào đây..."
                  className="w-full px-5 py-4 text-lg rounded-2xl border-2 border-sakura-100 focus:outline-none focus:border-sakura-400 focus:ring-4 focus:ring-sakura-50 transition-all text-gray-700 placeholder-gray-400 bg-gray-50 focus:bg-white"
                  value={originalUrl}
                  onChange={(e) => setOriginalUrl(e.target.value)}
                />
              </div>

              <div className="flex items-center rounded-2xl border-2 border-sakura-100 overflow-hidden focus-within:border-sakura-400 focus-within:ring-4 focus-within:ring-sakura-50 transition-all bg-gray-50 focus-within:bg-white">
                <span className="px-5 py-4 text-gray-400 font-medium border-r border-sakura-100">
                  nanolink.local/
                </span>
                <input
                  type="text"
                  placeholder="ma-tuy-chinh"
                  className="w-full px-4 py-4 text-lg focus:outline-none text-gray-700 bg-transparent"
                  value={customAlias}
                  onChange={(e) => setCustomAlias(e.target.value)}
                />
              </div>

              {error && <div className="text-rose-600 font-medium text-sm bg-rose-50 p-3 rounded-xl border border-rose-100 animate-[fadeIn_0.3s_ease-out]">{error}</div>}

              <button
                type="submit"
                disabled={loading}
                className="w-full py-4 rounded-2xl font-bold text-lg text-white bg-gradient-to-r from-sakura-500 to-rose-500 hover:from-sakura-600 hover:to-rose-600 transition-all transform hover:scale-[1.02] shadow-lg hover:shadow-sakura-300/50 disabled:opacity-70 disabled:hover:scale-100"
              >
                {loading ? 'Đang vắt kiệt sức server...' : '✂️ Rút Gọn Ngay'}
              </button>
            </form>

            {/* Result Box */}
            {result && (
              <div className="mt-6 p-5 bg-sakura-50 rounded-2xl border border-sakura-200 animate-[fadeIn_0.5s_ease-out]">
                <div className="flex justify-between items-center mb-3">
                  <span className="text-sm font-bold text-sakura-600 uppercase tracking-wider">Link của bạn đã sẵn sàng</span>
                </div>
                <div className="flex gap-2 mb-4">
                  <input 
                    type="text" 
                    readOnly 
                    value={result.short_url} 
                    className="flex-1 bg-white border border-sakura-200 rounded-xl px-4 py-3 font-semibold text-gray-800 outline-none w-full"
                  />
                  <button 
                    onClick={() => navigator.clipboard.writeText(result.short_url)}
                    className="px-6 py-3 bg-sakura-600 text-white rounded-xl hover:bg-sakura-700 transition-colors font-bold shadow-sm"
                  >
                    Copy
                  </button>
                </div>
                {result.qr_code && (
                  <div className="flex justify-center mt-4">
                    <div className="p-2 bg-white rounded-xl shadow-sm border border-sakura-100">
                      <img src={result.qr_code} alt="QR Code" className="w-32 h-32 object-contain" />
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Illustration/Features */}
        <div className="w-full lg:w-1/2 flex flex-col gap-6">
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-sakura-100 flex items-start gap-4 hover:shadow-md transition-shadow">
            <div className="w-12 h-12 bg-sakura-100 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">🚀</div>
            <div>
              <h3 className="font-bold text-gray-900 text-lg mb-1">Tốc độ ánh sáng</h3>
              <p className="text-gray-600 text-sm leading-relaxed">Chuyển hướng người dùng trong tích tắc. Database tối ưu index đảm bảo không có độ trễ.</p>
            </div>
          </div>
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-sakura-100 flex items-start gap-4 hover:shadow-md transition-shadow">
            <div className="w-12 h-12 bg-sakura-100 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">🎯</div>
            <div>
              <h3 className="font-bold text-gray-900 text-lg mb-1">Tùy chỉnh Alias</h3>
              <p className="text-gray-600 text-sm leading-relaxed">Tạo đường link mang thương hiệu cá nhân thay vì một mớ ký tự lộn xộn vô nghĩa.</p>
            </div>
          </div>
          <div className="bg-white p-6 rounded-3xl shadow-sm border border-sakura-100 flex items-start gap-4 hover:shadow-md transition-shadow">
            <div className="w-12 h-12 bg-sakura-100 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">📱</div>
            <div>
              <h3 className="font-bold text-gray-900 text-lg mb-1">Mã QR Code Tự động</h3>
              <p className="text-gray-600 text-sm leading-relaxed">Tự động sinh mã QR chất lượng cao. Khách hàng chỉ cần quét là truy cập ngay.</p>
            </div>
          </div>
        </div>

      </main>

      {/* FOOTER */}
      <footer className="w-full bg-white border-t border-sakura-100 py-8 mt-12">
        <div className="max-w-5xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
          <p className="text-sm text-gray-500 font-medium">
            © 2026 NanoLink. Được nhào nặn bởi <span className="text-sakura-600 font-bold">Một Dev Thích Màu Hường</span>.
          </p>
          <div className="flex gap-4 text-sm text-gray-400">
            <a href="#" className="hover:text-sakura-500 transition-colors">Báo cáo lỗi</a>
            <span>•</span>
            <a href="#" className="hover:text-sakura-500 transition-colors">Điều khoản (Đọc cho vui)</a>
          </div>
        </div>
      </footer>

    </div>
  );
}