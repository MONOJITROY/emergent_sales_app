import { useEffect, useState } from "react";
import { api, money } from "@/lib/api";
import { TrendingUp, AlertTriangle, Users, Package } from "lucide-react";
import { ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid } from "recharts";
import { Link } from "react-router-dom";
import { Badge } from "@/components/ui/badge";

const KPI = ({ label, value, icon: Icon, hint, testid }) => (
  <div className="surface p-4" data-testid={testid}>
    <div className="flex items-start justify-between">
      <div>
        <div className="label-tiny">{label}</div>
        <div className="mt-1 text-2xl font-bold text-num tracking-tight">{value}</div>
        {hint && <div className="text-xs text-slate-500 mt-1">{hint}</div>}
      </div>
      <div className="h-8 w-8 grid place-items-center rounded bg-orange-50 text-orange-600">
        <Icon className="h-4 w-4" />
      </div>
    </div>
  </div>
);

export default function Dashboard() {
  const [s, setS] = useState(null);

  useEffect(() => {
    api.get("/dashboard/stats").then((r) => setS(r.data));
  }, []);

  if (!s) return <div className="text-slate-500 text-sm">Loading…</div>;

  return (
    <div className="space-y-6" data-testid="dashboard-page">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Operations Overview</h1>
        <p className="text-sm text-slate-500">A snapshot of today’s activity.</p>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KPI label="Today's Sales" value={money(s.today_sales)} icon={TrendingUp} hint="USD" testid="kpi-today-sales" />
        <KPI label="Outstanding" value={money(s.outstanding)} icon={AlertTriangle} hint="Across all unpaid" testid="kpi-outstanding" />
        <KPI label="Products" value={s.products_count} icon={Package} hint={`${s.low_stock_count} low`} testid="kpi-products" />
        <KPI label="Customers" value={s.customers_count} icon={Users} testid="kpi-customers" />
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        <div className="surface p-4 lg:col-span-2" data-testid="sales-chart">
          <div className="flex items-center justify-between mb-3">
            <div>
              <div className="label-tiny">Sales · last 7 days</div>
              <div className="text-lg font-semibold">{money(s.total_sales)}</div>
            </div>
          </div>
          <div style={{ width: "100%", height: 240 }}>
            <ResponsiveContainer>
              <BarChart data={s.chart}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                <XAxis dataKey="date" tickFormatter={(d) => d.slice(5)} fontSize={11} tick={{ fill: "#64748b" }} />
                <YAxis fontSize={11} tick={{ fill: "#64748b" }} />
                <Tooltip cursor={{ fill: "rgba(234,88,12,0.08)" }} contentStyle={{ fontSize: 12, border: "1px solid #e2e8f0", borderRadius: 4 }} />
                <Bar dataKey="total" fill="#ea580c" radius={[2,2,0,0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="surface p-4" data-testid="low-stock">
          <div className="label-tiny mb-2">Low Stock</div>
          {s.low_stock_items.length === 0 ? (
            <div className="text-sm text-slate-500 py-6 text-center">All stock levels healthy.</div>
          ) : (
            <ul className="divide-y divide-slate-100">
              {s.low_stock_items.map((p) => (
                <li key={p.id} className="py-2 flex items-center justify-between">
                  <div>
                    <div className="text-sm font-medium">{p.name}</div>
                    <div className="text-xs text-slate-500 font-mono">{p.sku}</div>
                  </div>
                  <Badge variant="outline" className="text-orange-700 border-orange-300 bg-orange-50 font-mono">{p.stock} {p.unit}</Badge>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>

      <div className="surface p-4">
        <div className="flex items-center justify-between mb-3">
          <div className="label-tiny">Recent invoices</div>
          <Link to="/sales" className="text-xs text-orange-600 hover:underline" data-testid="view-all-sales">View all →</Link>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
              <th className="py-2">Invoice</th><th>Customer</th><th>Date</th>
              <th className="text-right">Total</th><th className="text-right">Balance</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            {s.recent_sales.length === 0 && <tr><td colSpan={6} className="py-6 text-center text-slate-500">No sales yet.</td></tr>}
            {s.recent_sales.map((r) => (
              <tr key={r.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="py-2 font-mono text-xs">{r.invoice_no}</td>
                <td>{r.customer_name}</td>
                <td className="text-xs text-slate-500">{r.date.slice(0,10)}</td>
                <td className="text-right text-num">{money(r.total)}</td>
                <td className="text-right text-num">{money(r.balance)}</td>
                <td><StatusBadge status={r.status} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

function StatusBadge({ status }) {
  const map = {
    paid: "bg-emerald-50 text-emerald-700 border-emerald-200",
    partial: "bg-amber-50 text-amber-700 border-amber-200",
    unpaid: "bg-red-50 text-red-700 border-red-200",
  };
  return <span className={`text-[11px] uppercase font-semibold border rounded px-1.5 py-0.5 ${map[status] || ""}`}>{status}</span>;
}
