import { NavLink, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "@/contexts/AuthContext";
import {
  LayoutDashboard, Package, Users, Truck, FileText, ShoppingCart,
  BarChart3, UserCog, LogOut, Menu, X
} from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";

const NAV = [
  { to: "/", label: "Dashboard", icon: LayoutDashboard, end: true, testid: "nav-dashboard" },
  { to: "/products", label: "Products", icon: Package, testid: "nav-products" },
  { to: "/customers", label: "Customers", icon: Users, testid: "nav-customers" },
  { to: "/suppliers", label: "Suppliers", icon: Truck, testid: "nav-suppliers" },
  { to: "/sales", label: "Sales", icon: FileText, testid: "nav-sales" },
  { to: "/purchases", label: "Purchases", icon: ShoppingCart, testid: "nav-purchases" },
  { to: "/reports", label: "Reports", icon: BarChart3, testid: "nav-reports" },
  { to: "/users", label: "Users", icon: UserCog, adminOnly: true, testid: "nav-users" },
];

export default function Layout() {
  const { user, logout } = useAuth();
  const [open, setOpen] = useState(false);
  const location = useLocation();

  const items = NAV.filter((n) => !n.adminOnly || user?.role === "admin");
  const current = items.find((n) => n.end ? location.pathname === "/" : location.pathname.startsWith(n.to));

  return (
    <div className="min-h-screen flex bg-slate-50">
      {/* Sidebar */}
      <aside
        className={`fixed lg:static inset-y-0 left-0 z-30 w-60 bg-slate-900 text-slate-100 flex flex-col transition-transform ${open ? "translate-x-0" : "-translate-x-full"} lg:translate-x-0`}
        data-testid="app-sidebar"
      >
        <div className="h-14 flex items-center px-5 border-b border-slate-800">
          <div className="flex items-center gap-2">
            <div className="h-6 w-6 bg-orange-600 rounded-sm grid place-items-center text-[10px] font-bold tracking-tighter">SF</div>
            <span className="font-bold tracking-tight text-base" style={{fontFamily:"Chivo"}}>StockFlow</span>
          </div>
        </div>
        <nav className="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">
          {items.map((n) => (
            <NavLink
              key={n.to}
              to={n.to}
              end={n.end}
              data-testid={n.testid}
              onClick={() => setOpen(false)}
              className={({ isActive }) =>
                `flex items-center gap-2.5 px-3 h-9 rounded text-[13px] transition-colors ${
                  isActive ? "bg-slate-800 text-white border-l-2 border-orange-500" : "text-slate-300 hover:bg-slate-800/60 hover:text-white border-l-2 border-transparent"
                }`
              }
            >
              <n.icon className="h-4 w-4" />
              <span>{n.label}</span>
            </NavLink>
          ))}
        </nav>
        <div className="border-t border-slate-800 p-3">
          <div className="text-[11px] text-slate-400 uppercase tracking-wider">Signed in as</div>
          <div className="text-sm font-medium truncate">{user?.name}</div>
          <div className="text-xs text-slate-400 truncate">{user?.email} · {user?.role}</div>
          <Button
            size="sm"
            variant="outline"
            data-testid="logout-btn"
            onClick={logout}
            className="mt-2 w-full bg-transparent border-slate-700 text-slate-200 hover:bg-slate-800 hover:text-white"
          >
            <LogOut className="h-3.5 w-3.5 mr-2" /> Sign out
          </Button>
        </div>
      </aside>

      {/* Mobile overlay */}
      {open && (
        <div className="fixed inset-0 bg-black/40 z-20 lg:hidden" onClick={() => setOpen(false)} />
      )}

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-14 bg-white border-b border-slate-200 flex items-center px-4 lg:px-6 gap-3 no-print">
          <button
            className="lg:hidden p-1.5 rounded hover:bg-slate-100"
            data-testid="sidebar-toggle"
            onClick={() => setOpen((v) => !v)}
            aria-label="Toggle menu"
          >
            {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
          <div className="flex items-center gap-2 text-sm">
            <span className="label-tiny">StockFlow</span>
            <span className="text-slate-300">/</span>
            <span className="font-medium text-slate-800">{current?.label || "Page"}</span>
          </div>
          <div className="ml-auto text-xs text-slate-500 hidden sm:block">
            {new Date().toLocaleDateString(undefined, { weekday: "short", month: "short", day: "numeric", year: "numeric" })}
          </div>
        </header>
        <main className="flex-1 overflow-x-auto p-4 lg:p-6 print-area">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
