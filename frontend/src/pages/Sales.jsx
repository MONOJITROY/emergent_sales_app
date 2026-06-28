import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api, money } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Plus, Search } from "lucide-react";

function StatusBadge({ status }) {
  const map = {
    paid: "bg-emerald-50 text-emerald-700 border-emerald-200",
    partial: "bg-amber-50 text-amber-700 border-amber-200",
    unpaid: "bg-red-50 text-red-700 border-red-200",
  };
  return <span className={`text-[11px] uppercase font-semibold border rounded px-1.5 py-0.5 ${map[status] || ""}`}>{status}</span>;
}

export default function Sales() {
  const [rows, setRows] = useState([]);
  const [q, setQ] = useState("");

  useEffect(() => {
    api.get("/sales", { params: q ? { q } : {} }).then((r) => setRows(r.data));
  }, [q]);

  return (
    <div className="space-y-4" data-testid="sales-page">
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Sales / Invoices</h1>
          <p className="text-sm text-slate-500">All issued invoices with payment status.</p>
        </div>
        <div className="flex gap-2 items-center">
          <div className="relative">
            <Search className="h-3.5 w-3.5 absolute left-2.5 top-2.5 text-slate-400" />
            <Input data-testid="sales-search" placeholder="Search invoice or customer…" value={q} onChange={(e) => setQ(e.target.value)} className="h-9 pl-8 w-72" />
          </div>
          <Link to="/sales/new"><Button data-testid="new-sale-btn" className="h-9 bg-orange-600 hover:bg-orange-700"><Plus className="h-4 w-4 mr-1" />New Sale</Button></Link>
        </div>
      </div>

      <div className="surface overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 border-b border-slate-200">
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-3 py-2">Invoice</th><th>Customer</th><th>Date</th><th>Items</th>
              <th className="text-right">Total</th><th className="text-right">Paid</th><th className="text-right">Balance</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && <tr><td colSpan={8} className="py-8 text-center text-slate-500">No invoices yet.</td></tr>}
            {rows.map((r) => (
              <tr key={r.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="px-3 py-2"><Link to={`/sales/${r.id}`} className="font-mono text-xs text-orange-600 hover:underline" data-testid={`sale-link-${r.invoice_no}`}>{r.invoice_no}</Link></td>
                <td>{r.customer_name}</td>
                <td className="text-xs text-slate-500">{r.date.slice(0,10)}</td>
                <td className="text-slate-600 text-xs">{r.items?.length || 0}</td>
                <td className="text-right text-num">{money(r.total)}</td>
                <td className="text-right text-num">{money(r.paid)}</td>
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
