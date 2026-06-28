import { useEffect, useState } from "react";
import { api, formatError, money } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Plus, Search, Pencil, Trash2 } from "lucide-react";
import { toast } from "sonner";

const EMPTY = { sku: "", name: "", category: "", unit: "pcs", cost_price: 0, sale_price: 0, stock: 0, reorder_level: 0, description: "" };

export default function Products() {
  const [rows, setRows] = useState([]);
  const [q, setQ] = useState("");
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState(EMPTY);
  const [editing, setEditing] = useState(null);

  const load = async () => {
    const { data } = await api.get("/products", { params: q ? { q } : {} });
    setRows(data);
  };
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [q]);

  const openNew = () => { setEditing(null); setForm(EMPTY); setOpen(true); };
  const openEdit = (r) => { setEditing(r); setForm({ ...EMPTY, ...r }); setOpen(true); };

  const save = async () => {
    if (!form.sku || !form.name) return toast.error("SKU and name are required");
    try {
      const payload = {
        ...form,
        cost_price: Number(form.cost_price), sale_price: Number(form.sale_price),
        stock: Number(form.stock), reorder_level: Number(form.reorder_level),
      };
      if (editing) await api.put(`/products/${editing.id}`, payload);
      else await api.post("/products", payload);
      toast.success(editing ? "Product updated" : "Product created");
      setOpen(false); load();
    } catch (e) { toast.error(formatError(e)); }
  };

  const del = async (r) => {
    toast(`Delete "${r.name}"?`, {
      action: {
        label: "Delete",
        onClick: async () => {
          try { await api.delete(`/products/${r.id}`); toast.success("Deleted"); load(); }
          catch (e) { toast.error(formatError(e)); }
        }
      },
    });
  };

  return (
    <div className="space-y-4" data-testid="products-page">
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Products</h1>
          <p className="text-sm text-slate-500">Master list of items, with current stock.</p>
        </div>
        <div className="flex gap-2 items-center">
          <div className="relative">
            <Search className="h-3.5 w-3.5 absolute left-2.5 top-2.5 text-slate-400" />
            <Input data-testid="products-search" placeholder="Search SKU or name…" value={q} onChange={(e) => setQ(e.target.value)} className="h-9 pl-8 w-64" />
          </div>
          <Button data-testid="add-product-btn" onClick={openNew} className="h-9 bg-orange-600 hover:bg-orange-700"><Plus className="h-4 w-4 mr-1" />New Product</Button>
        </div>
      </div>

      <div className="surface overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 border-b border-slate-200">
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-3 py-2">SKU</th><th>Name</th><th>Category</th>
              <th className="text-right">Cost</th><th className="text-right">Price</th>
              <th className="text-right">Stock</th><th className="text-right">Reorder</th>
              <th className="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && <tr><td colSpan={8} className="py-8 text-center text-slate-500">No products. Click &ldquo;New Product&rdquo;.</td></tr>}
            {rows.map((r) => {
              const low = r.reorder_level > 0 && r.stock <= r.reorder_level;
              return (
                <tr key={r.id} className="border-b border-slate-100 hover:bg-slate-50" data-testid={`product-row-${r.sku}`}>
                  <td className="px-3 py-2 font-mono text-xs">{r.sku}</td>
                  <td className="font-medium">{r.name}</td>
                  <td className="text-slate-600">{r.category || "—"}</td>
                  <td className="text-right text-num">{money(r.cost_price)}</td>
                  <td className="text-right text-num">{money(r.sale_price)}</td>
                  <td className={`text-right text-num ${low ? "text-orange-600 font-semibold" : ""}`}>{r.stock} {r.unit}</td>
                  <td className="text-right text-num text-slate-500">{r.reorder_level}</td>
                  <td className="text-right">
                    <button onClick={() => openEdit(r)} aria-label="Edit" data-testid={`edit-product-${r.sku}`} className="p-1.5 hover:bg-slate-100 rounded"><Pencil className="h-3.5 w-3.5" /></button>
                    <button onClick={() => del(r)} aria-label="Delete" data-testid={`delete-product-${r.sku}`} className="p-1.5 hover:bg-red-50 hover:text-red-600 rounded"><Trash2 className="h-3.5 w-3.5" /></button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="md:max-w-2xl" data-testid="product-dialog">
          <DialogHeader><DialogTitle>{editing ? "Edit product" : "New product"}</DialogTitle></DialogHeader>
          <div className="grid grid-cols-2 gap-3">
            <Field label="SKU *" value={form.sku} onChange={(v) => setForm({ ...form, sku: v })} testid="product-sku" />
            <Field label="Name *" value={form.name} onChange={(v) => setForm({ ...form, name: v })} testid="product-name" />
            <Field label="Category" value={form.category} onChange={(v) => setForm({ ...form, category: v })} testid="product-category" />
            <Field label="Unit" value={form.unit} onChange={(v) => setForm({ ...form, unit: v })} testid="product-unit" />
            <Field label="Cost price" type="number" value={form.cost_price} onChange={(v) => setForm({ ...form, cost_price: v })} testid="product-cost" />
            <Field label="Sale price" type="number" value={form.sale_price} onChange={(v) => setForm({ ...form, sale_price: v })} testid="product-price" />
            <Field label="Stock" type="number" value={form.stock} onChange={(v) => setForm({ ...form, stock: v })} testid="product-stock" />
            <Field label="Reorder level" type="number" value={form.reorder_level} onChange={(v) => setForm({ ...form, reorder_level: v })} testid="product-reorder" />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button onClick={save} className="bg-orange-600 hover:bg-orange-700" data-testid="save-product-btn">Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function Field({ label, value, onChange, type = "text", testid }) {
  return (
    <div>
      <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">{label}</Label>
      <Input type={type} value={value ?? ""} onChange={(e) => onChange(e.target.value)} className="h-9 mt-1" data-testid={testid} />
    </div>
  );
}
