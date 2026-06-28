import { useEffect, useMemo, useState } from "react";
import { api, formatError, money } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Plus, Search, Trash2 } from "lucide-react";
import { toast } from "sonner";

export default function Purchases() {
  const [rows, setRows] = useState([]);
  const [products, setProducts] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  const [q, setQ] = useState("");
  const [open, setOpen] = useState(false);
  const [supplierId, setSupplierId] = useState("");
  const [supplierName, setSupplierName] = useState("");
  const [items, setItems] = useState([]);
  const [tax, setTax] = useState(0);
  const [notes, setNotes] = useState("");

  const load = async () => {
    const { data } = await api.get("/purchases", { params: q ? { q } : {} });
    setRows(data);
  };
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [q]);
  useEffect(() => {
    api.get("/products").then((r) => setProducts(r.data));
    api.get("/suppliers").then((r) => setSuppliers(r.data));
  }, []);

  const subtotal = useMemo(() => items.reduce((s, it) => s + Number(it.total || 0), 0), [items]);
  const total = subtotal + Number(tax || 0);

  const addItem = () => setItems([...items, { product_id: "", sku: "", name: "", qty: 1, price: 0, total: 0 }]);
  const updateItem = (i, patch) => {
    const next = items.map((it, idx) => idx === i ? { ...it, ...patch } : it);
    const row = next[i];
    if (patch.product_id) {
      const p = products.find((x) => x.id === patch.product_id);
      if (p) { row.sku = p.sku; row.name = p.name; row.price = p.cost_price; }
    }
    row.total = Number(row.qty || 0) * Number(row.price || 0);
    next[i] = row; setItems(next);
  };
  const removeItem = (i) => setItems(items.filter((_, idx) => idx !== i));

  const openNew = () => {
    setSupplierId(""); setSupplierName(""); setItems([]); setTax(0); setNotes(""); setOpen(true);
  };

  const submit = async () => {
    if (!supplierName) return toast.error("Supplier name required");
    if (items.length === 0) return toast.error("Add at least one item");
    if (items.some((it) => !it.product_id || it.qty <= 0)) return toast.error("Each line needs a product and qty > 0");
    try {
      const payload = {
        supplier_id: supplierId || null,
        supplier_name: supplierName,
        items: items.map((it) => ({ ...it, qty: Number(it.qty), price: Number(it.price), total: Number(it.total) })),
        tax: Number(tax), notes,
      };
      const { data } = await api.post("/purchases", payload);
      toast.success(`Purchase ${data.ref_no} recorded`);
      setOpen(false); load();
    } catch (e) { toast.error(formatError(e)); }
  };

  const del = (r) => {
    toast(`Delete purchase ${r.ref_no}?`, {
      action: { label: "Delete", onClick: async () => {
        try { await api.delete(`/purchases/${r.id}`); toast.success("Deleted"); load(); }
        catch (e) { toast.error(formatError(e)); }
      }}
    });
  };

  return (
    <div className="space-y-4" data-testid="purchases-page">
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Purchases</h1>
          <p className="text-sm text-slate-500">Stock receipts from suppliers.</p>
        </div>
        <div className="flex gap-2 items-center">
          <div className="relative">
            <Search className="h-3.5 w-3.5 absolute left-2.5 top-2.5 text-slate-400" />
            <Input data-testid="purchases-search" placeholder="Search ref or supplier…" value={q} onChange={(e) => setQ(e.target.value)} className="h-9 pl-8 w-64" />
          </div>
          <Button onClick={openNew} className="h-9 bg-orange-600 hover:bg-orange-700" data-testid="new-purchase-btn"><Plus className="h-4 w-4 mr-1" />New Purchase</Button>
        </div>
      </div>

      <div className="surface overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 border-b border-slate-200">
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-3 py-2">Ref</th><th>Supplier</th><th>Date</th><th>Items</th>
              <th className="text-right">Subtotal</th><th className="text-right">Tax</th><th className="text-right">Total</th><th className="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && <tr><td colSpan={8} className="py-8 text-center text-slate-500">No purchases yet.</td></tr>}
            {rows.map((r) => (
              <tr key={r.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="px-3 py-2 font-mono text-xs">{r.ref_no}</td>
                <td>{r.supplier_name}</td>
                <td className="text-xs text-slate-500">{r.date.slice(0,10)}</td>
                <td className="text-slate-600 text-xs">{r.items?.length || 0}</td>
                <td className="text-right text-num">{money(r.subtotal)}</td>
                <td className="text-right text-num">{money(r.tax)}</td>
                <td className="text-right text-num font-semibold">{money(r.total)}</td>
                <td className="text-right"><button onClick={() => del(r)} aria-label="Delete" className="p-1.5 hover:bg-red-50 hover:text-red-600 rounded"><Trash2 className="h-3.5 w-3.5" /></button></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="md:max-w-3xl">
          <DialogHeader><DialogTitle>New purchase</DialogTitle></DialogHeader>
          <div className="grid md:grid-cols-2 gap-3 mb-3">
            <div>
              <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Supplier</Label>
              <Select value={supplierId} onValueChange={(v) => {
                setSupplierId(v);
                const s = suppliers.find((x) => x.id === v);
                if (s) setSupplierName(s.name);
              }}>
                <SelectTrigger className="h-9 mt-1" data-testid="supplier-select"><SelectValue placeholder="Select supplier" /></SelectTrigger>
                <SelectContent>
                  {suppliers.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Supplier name *</Label>
              <Input value={supplierName} onChange={(e) => setSupplierName(e.target.value)} className="h-9 mt-1" />
            </div>
          </div>

          <div className="border border-slate-200 rounded">
            <div className="px-3 py-2 bg-slate-50 border-b flex justify-between items-center">
              <div className="label-tiny">Items</div>
              <Button size="sm" variant="outline" className="h-7" onClick={addItem}><Plus className="h-3 w-3 mr-1" />Add</Button>
            </div>
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-xs uppercase tracking-wide text-slate-500 border-b">
                  <th className="px-2 py-1.5 w-2/5">Product</th>
                  <th className="text-right">Qty</th>
                  <th className="text-right">Cost</th>
                  <th className="text-right">Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {items.length === 0 && <tr><td colSpan={5} className="py-4 text-center text-slate-500 text-xs">No items.</td></tr>}
                {items.map((it, i) => (
                  <tr key={i} className="border-b">
                    <td className="px-2 py-1.5">
                      <Select value={it.product_id} onValueChange={(v) => updateItem(i, { product_id: v })}>
                        <SelectTrigger className="h-8"><SelectValue placeholder="Product" /></SelectTrigger>
                        <SelectContent>
                          {products.map((p) => <SelectItem key={p.id} value={p.id}>{p.sku} — {p.name}</SelectItem>)}
                        </SelectContent>
                      </Select>
                    </td>
                    <td className="text-right"><Input type="number" min="0" step="any" value={it.qty} onChange={(e) => updateItem(i, { qty: e.target.value })} className="h-8 text-right w-20 ml-auto" /></td>
                    <td className="text-right"><Input type="number" min="0" step="any" value={it.price} onChange={(e) => updateItem(i, { price: e.target.value })} className="h-8 text-right w-24 ml-auto" /></td>
                    <td className="text-right text-num pr-2">{money(it.total)}</td>
                    <td className="text-right pr-2"><button onClick={() => removeItem(i)} className="p-1 hover:bg-red-50 rounded"><Trash2 className="h-3.5 w-3.5" /></button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="grid md:grid-cols-2 gap-3 mt-3">
            <div>
              <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Notes</Label>
              <textarea value={notes} onChange={(e) => setNotes(e.target.value)} rows={3} className="w-full mt-1 border rounded text-sm p-2" />
            </div>
            <div className="space-y-1 text-sm">
              <div className="flex justify-between"><span>Subtotal</span><span className="text-num">{money(subtotal)}</span></div>
              <div className="flex items-center justify-between gap-2">
                <span>Tax</span>
                <Input type="number" min="0" step="any" value={tax} onChange={(e) => setTax(e.target.value)} className="h-8 w-28 text-right" />
              </div>
              <div className="flex justify-between font-bold text-orange-600 border-t pt-1"><span>Total</span><span className="text-num">{money(total)}</span></div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button onClick={submit} className="bg-orange-600 hover:bg-orange-700" data-testid="save-purchase-btn">Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
