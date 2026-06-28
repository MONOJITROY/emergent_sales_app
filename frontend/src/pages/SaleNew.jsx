import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api, formatError, money } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Plus, Trash2, ArrowLeft } from "lucide-react";
import { toast } from "sonner";

export default function SaleNew() {
  const navigate = useNavigate();
  const [products, setProducts] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [customerId, setCustomerId] = useState("walkin");
  const [customerName, setCustomerName] = useState("Walk-in customer");
  const [items, setItems] = useState([]);
  const [discount, setDiscount] = useState(0);
  const [tax, setTax] = useState(0);
  const [paid, setPaid] = useState(0);
  const [notes, setNotes] = useState("");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    api.get("/products").then((r) => setProducts(r.data));
    api.get("/customers").then((r) => setCustomers(r.data));
  }, []);

  const addItem = () => setItems([...items, { product_id: "", sku: "", name: "", qty: 1, price: 0, total: 0 }]);

  const updateItem = (i, patch) => {
    const next = items.map((it, idx) => idx === i ? { ...it, ...patch } : it);
    const row = next[i];
    if (patch.product_id) {
      const p = products.find((x) => x.id === patch.product_id);
      if (p) { row.sku = p.sku; row.name = p.name; row.price = p.sale_price; }
    }
    row.total = Number(row.qty || 0) * Number(row.price || 0);
    next[i] = row;
    setItems(next);
  };

  const removeItem = (i) => setItems(items.filter((_, idx) => idx !== i));

  const subtotal = useMemo(() => items.reduce((s, it) => s + Number(it.total || 0), 0), [items]);
  const total = Math.max(0, subtotal - Number(discount || 0) + Number(tax || 0));
  const balance = Math.max(0, total - Number(paid || 0));

  const submit = async () => {
    if (items.length === 0) return toast.error("Add at least one item");
    if (items.some((it) => !it.product_id || it.qty <= 0)) return toast.error("Each line needs a product and qty > 0");
    setSaving(true);
    try {
      const payload = {
        customer_id: customerId === "walkin" ? null : customerId,
        customer_name: customerName || "Walk-in customer",
        items: items.map((it) => ({ ...it, qty: Number(it.qty), price: Number(it.price), total: Number(it.total) })),
        discount: Number(discount), tax: Number(tax), paid: Number(paid), notes,
      };
      const { data } = await api.post("/sales", payload);
      toast.success(`Invoice ${data.invoice_no} created`);
      navigate(`/sales/${data.id}`);
    } catch (e) { toast.error(formatError(e)); }
    finally { setSaving(false); }
  };

  return (
    <div className="space-y-4" data-testid="sale-new-page">
      <button onClick={() => navigate(-1)} className="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1"><ArrowLeft className="h-3.5 w-3.5" /> Back</button>
      <div>
        <h1 className="text-2xl font-bold tracking-tight">New Sale</h1>
        <p className="text-sm text-slate-500">Create an invoice and decrement stock.</p>
      </div>

      <div className="surface p-4">
        <div className="grid md:grid-cols-2 gap-3">
          <div>
            <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Customer</Label>
            <Select value={customerId} onValueChange={(v) => {
              setCustomerId(v);
              const c = customers.find((x) => x.id === v);
              setCustomerName(c ? c.name : "Walk-in customer");
            }}>
              <SelectTrigger className="h-9 mt-1" data-testid="customer-select"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="walkin">Walk-in customer</SelectItem>
                {customers.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div>
            <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Customer name (override)</Label>
            <Input value={customerName} onChange={(e) => setCustomerName(e.target.value)} className="h-9 mt-1" />
          </div>
        </div>
      </div>

      <div className="surface">
        <div className="px-4 py-2 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
          <div className="label-tiny">Line items</div>
          <Button size="sm" onClick={addItem} variant="outline" className="h-8" data-testid="add-line-btn"><Plus className="h-3.5 w-3.5 mr-1" /> Add item</Button>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
              <th className="px-3 py-2 w-2/5">Product</th>
              <th className="text-right">Qty</th>
              <th className="text-right">Price</th>
              <th className="text-right">Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 && <tr><td colSpan={5} className="py-6 text-center text-slate-500 text-sm">No items yet.</td></tr>}
            {items.map((it, i) => (
              <tr key={i} className="border-b border-slate-100" data-testid={`line-row-${i}`}>
                <td className="px-3 py-2">
                  <Select value={it.product_id} onValueChange={(v) => updateItem(i, { product_id: v })}>
                    <SelectTrigger className="h-8" data-testid={`line-product-${i}`}><SelectValue placeholder="Select product" /></SelectTrigger>
                    <SelectContent>
                      {products.map((p) => <SelectItem key={p.id} value={p.id}>{p.sku} — {p.name} (stock {p.stock})</SelectItem>)}
                    </SelectContent>
                  </Select>
                </td>
                <td className="text-right"><Input type="number" min="0" step="any" value={it.qty} onChange={(e) => updateItem(i, { qty: e.target.value })} className="h-8 text-right w-20 ml-auto" data-testid={`line-qty-${i}`} /></td>
                <td className="text-right"><Input type="number" min="0" step="any" value={it.price} onChange={(e) => updateItem(i, { price: e.target.value })} className="h-8 text-right w-24 ml-auto" data-testid={`line-price-${i}`} /></td>
                <td className="text-right text-num pr-3">{money(it.total)}</td>
                <td className="text-right pr-3"><button onClick={() => removeItem(i)} className="p-1 hover:bg-red-50 hover:text-red-600 rounded"><Trash2 className="h-3.5 w-3.5" /></button></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="grid md:grid-cols-2 gap-4">
        <div className="surface p-4">
          <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Notes</Label>
          <textarea value={notes} onChange={(e) => setNotes(e.target.value)} rows={4} className="w-full mt-1 border border-slate-200 rounded text-sm p-2" />
        </div>
        <div className="surface p-4">
          <div className="space-y-2">
            <Row label="Subtotal" value={money(subtotal)} />
            <NumberRow label="Discount" value={discount} onChange={setDiscount} />
            <NumberRow label="Tax" value={tax} onChange={setTax} />
            <div className="border-t border-slate-200 pt-2 flex items-center justify-between">
              <div className="text-sm font-semibold">Total</div>
              <div className="text-xl font-bold text-num">{money(total)}</div>
            </div>
            <NumberRow label="Paid now" value={paid} onChange={setPaid} testid="paid-input" />
            <Row label="Balance" value={money(balance)} highlight />
          </div>
          <Button onClick={submit} disabled={saving} className="w-full mt-4 h-10 bg-orange-600 hover:bg-orange-700" data-testid="save-sale-btn">
            {saving ? "Saving…" : "Save Invoice"}
          </Button>
        </div>
      </div>
    </div>
  );
}

function Row({ label, value, highlight }) {
  return (
    <div className="flex items-center justify-between text-sm">
      <div className="text-slate-600">{label}</div>
      <div className={`text-num ${highlight ? "font-bold text-orange-600" : ""}`}>{value}</div>
    </div>
  );
}

function NumberRow({ label, value, onChange, testid }) {
  return (
    <div className="flex items-center justify-between gap-2">
      <div className="text-sm text-slate-600">{label}</div>
      <Input type="number" min="0" step="any" value={value} onChange={(e) => onChange(e.target.value)} className="h-8 w-28 text-right" data-testid={testid} />
    </div>
  );
}
