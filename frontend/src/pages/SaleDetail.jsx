import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { api, formatError, money } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ArrowLeft, Printer, Trash2 } from "lucide-react";
import { toast } from "sonner";

export default function SaleDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [s, setS] = useState(null);
  const [pay, setPay] = useState(0);

  const load = () => api.get(`/sales/${id}`).then((r) => setS(r.data));
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [id]);

  if (!s) return <div className="text-slate-500 text-sm">Loading…</div>;

  const recordPayment = async () => {
    if (Number(pay) <= 0) return toast.error("Enter a positive amount");
    try { await api.post(`/sales/${id}/payment`, { amount: Number(pay) }); toast.success("Payment recorded"); setPay(0); load(); }
    catch (e) { toast.error(formatError(e)); }
  };

  const del = () => {
    toast(`Delete invoice ${s.invoice_no}?`, {
      action: {
        label: "Delete",
        onClick: async () => {
          try { await api.delete(`/sales/${id}`); toast.success("Deleted"); navigate("/sales"); }
          catch (e) { toast.error(formatError(e)); }
        }
      }
    });
  };

  return (
    <div className="space-y-4" data-testid="sale-detail-page">
      <div className="flex items-center justify-between no-print">
        <button onClick={() => navigate(-1)} className="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1"><ArrowLeft className="h-3.5 w-3.5" /> Back</button>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => window.print()} data-testid="print-btn"><Printer className="h-3.5 w-3.5 mr-1" /> Print</Button>
          <Button variant="outline" size="sm" onClick={del} className="text-red-600 hover:text-red-700" data-testid="delete-sale-btn"><Trash2 className="h-3.5 w-3.5 mr-1" /> Delete</Button>
        </div>
      </div>

      <div className="surface p-6 print-area">
        <div className="flex items-start justify-between flex-wrap gap-4 mb-6 pb-4 border-b border-slate-200">
          <div>
            <div className="flex items-center gap-2 mb-2">
              <div className="h-6 w-6 bg-orange-600 rounded-sm grid place-items-center text-white text-[10px] font-bold">SF</div>
              <span className="font-bold tracking-tight" style={{ fontFamily: "Chivo" }}>StockFlow</span>
            </div>
            <div className="text-xs text-slate-500">Sales & Inventory · Demo</div>
          </div>
          <div className="text-right">
            <div className="text-[11px] uppercase tracking-wider text-slate-500">Invoice</div>
            <div className="font-mono text-lg font-semibold">{s.invoice_no}</div>
            <div className="text-xs text-slate-500 mt-1">Date: {s.date.slice(0, 10)}</div>
          </div>
        </div>

        <div className="grid md:grid-cols-2 gap-6 mb-6">
          <div>
            <div className="label-tiny mb-1">Bill to</div>
            <div className="font-semibold">{s.customer_name}</div>
          </div>
          <div className="md:text-right">
            <div className="label-tiny mb-1">Status</div>
            <span className={`text-[11px] uppercase font-semibold border rounded px-2 py-0.5 ${s.status === "paid" ? "bg-emerald-50 text-emerald-700 border-emerald-200" : s.status === "partial" ? "bg-amber-50 text-amber-700 border-amber-200" : "bg-red-50 text-red-700 border-red-200"}`}>{s.status}</span>
          </div>
        </div>

        <table className="w-full text-sm mb-6">
          <thead>
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
              <th className="py-2">SKU</th><th>Item</th>
              <th className="text-right">Qty</th><th className="text-right">Price</th><th className="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            {s.items.map((it, i) => (
              <tr key={i} className="border-b border-slate-100">
                <td className="py-2 font-mono text-xs">{it.sku}</td>
                <td>{it.name}</td>
                <td className="text-right text-num">{it.qty}</td>
                <td className="text-right text-num">{money(it.price)}</td>
                <td className="text-right text-num">{money(it.total)}</td>
              </tr>
            ))}
          </tbody>
        </table>

        <div className="flex justify-end">
          <div className="w-full max-w-xs space-y-1 text-sm">
            <div className="flex justify-between"><span className="text-slate-600">Subtotal</span><span className="text-num">{money(s.subtotal)}</span></div>
            <div className="flex justify-between"><span className="text-slate-600">Discount</span><span className="text-num">-{money(s.discount)}</span></div>
            <div className="flex justify-between"><span className="text-slate-600">Tax</span><span className="text-num">+{money(s.tax)}</span></div>
            <div className="flex justify-between border-t border-slate-200 pt-1 font-semibold"><span>Total</span><span className="text-num">{money(s.total)}</span></div>
            <div className="flex justify-between text-slate-600"><span>Paid</span><span className="text-num">{money(s.paid)}</span></div>
            <div className="flex justify-between font-bold text-orange-600"><span>Balance</span><span className="text-num">{money(s.balance)}</span></div>
          </div>
        </div>

        {s.notes && <div className="mt-6 pt-4 border-t border-slate-200 text-sm"><div className="label-tiny mb-1">Notes</div><div className="text-slate-700 whitespace-pre-wrap">{s.notes}</div></div>}
      </div>

      {s.balance > 0 && (
        <div className="surface p-4 no-print">
          <div className="label-tiny mb-2">Record payment</div>
          <div className="flex gap-2 items-center">
            <Input type="number" min="0" step="any" value={pay} onChange={(e) => setPay(e.target.value)} className="h-9 max-w-[180px]" placeholder="Amount" data-testid="payment-amount-input" />
            <Button onClick={recordPayment} className="h-9 bg-orange-600 hover:bg-orange-700" data-testid="record-payment-btn">Record</Button>
          </div>
        </div>
      )}
    </div>
  );
}
