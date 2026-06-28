import { useEffect, useState } from "react";
import { api, money } from "@/lib/api";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

export default function Reports() {
  const [byCustomer, setByCustomer] = useState([]);
  const [byProduct, setByProduct] = useState([]);
  const [aging, setAging] = useState({ buckets: {}, rows: [] });

  useEffect(() => {
    api.get("/reports/sales-by-customer").then((r) => setByCustomer(r.data));
    api.get("/reports/sales-by-product").then((r) => setByProduct(r.data));
    api.get("/reports/invoice-aging").then((r) => setAging(r.data));
  }, []);

  return (
    <div className="space-y-4" data-testid="reports-page">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Reports</h1>
        <p className="text-sm text-slate-500">Operational insights across sales and receivables.</p>
      </div>

      <Tabs defaultValue="customer">
        <TabsList>
          <TabsTrigger value="customer" data-testid="tab-by-customer">Sales by Customer</TabsTrigger>
          <TabsTrigger value="product" data-testid="tab-by-product">Sales by Product</TabsTrigger>
          <TabsTrigger value="aging" data-testid="tab-aging">Invoice Aging</TabsTrigger>
        </TabsList>

        <TabsContent value="customer" className="surface mt-3 overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
                <th className="px-3 py-2">Customer</th><th className="text-right">Invoices</th><th className="text-right">Total</th><th className="text-right">Balance</th>
              </tr>
            </thead>
            <tbody>
              {byCustomer.length === 0 && <tr><td colSpan={4} className="py-8 text-center text-slate-500">No data.</td></tr>}
              {byCustomer.map((r, i) => (
                <tr key={i} className="border-b border-slate-100 hover:bg-slate-50">
                  <td className="px-3 py-2 font-medium">{r.customer}</td>
                  <td className="text-right text-num">{r.invoices}</td>
                  <td className="text-right text-num">{money(r.total)}</td>
                  <td className={`text-right text-num ${r.balance > 0 ? "text-red-600" : ""}`}>{money(r.balance)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </TabsContent>

        <TabsContent value="product" className="surface mt-3 overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
                <th className="px-3 py-2">SKU</th><th>Product</th><th className="text-right">Qty Sold</th><th className="text-right">Revenue</th>
              </tr>
            </thead>
            <tbody>
              {byProduct.length === 0 && <tr><td colSpan={4} className="py-8 text-center text-slate-500">No data.</td></tr>}
              {byProduct.map((r, i) => (
                <tr key={i} className="border-b border-slate-100 hover:bg-slate-50">
                  <td className="px-3 py-2 font-mono text-xs">{r.sku}</td>
                  <td className="font-medium">{r.product}</td>
                  <td className="text-right text-num">{r.qty}</td>
                  <td className="text-right text-num">{money(r.total)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </TabsContent>

        <TabsContent value="aging" className="mt-3 space-y-3">
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            {["0-30", "31-60", "61-90", "90+"].map((k) => (
              <div key={k} className="surface p-3">
                <div className="label-tiny">{k} days</div>
                <div className="text-xl font-bold text-num mt-1">{money(aging.buckets?.[k] || 0)}</div>
              </div>
            ))}
          </div>
          <div className="surface overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 border-b border-slate-200">
                <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
                  <th className="px-3 py-2">Invoice</th><th>Customer</th><th>Date</th>
                  <th className="text-right">Days</th><th>Bucket</th>
                  <th className="text-right">Total</th><th className="text-right">Balance</th>
                </tr>
              </thead>
              <tbody>
                {aging.rows?.length === 0 && <tr><td colSpan={7} className="py-8 text-center text-slate-500">No outstanding invoices.</td></tr>}
                {aging.rows?.map((r) => (
                  <tr key={r.invoice_no} className="border-b border-slate-100 hover:bg-slate-50">
                    <td className="px-3 py-2 font-mono text-xs">{r.invoice_no}</td>
                    <td>{r.customer}</td>
                    <td className="text-xs text-slate-500">{r.date}</td>
                    <td className="text-right text-num">{r.days_overdue}</td>
                    <td><span className="text-[11px] uppercase font-semibold border rounded px-1.5 py-0.5 bg-slate-50 border-slate-200">{r.bucket}</span></td>
                    <td className="text-right text-num">{money(r.total)}</td>
                    <td className="text-right text-num text-red-600">{money(r.balance)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}
