import { useEffect, useState } from "react";
import { api, formatError, money } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Plus, Search, Pencil, Trash2 } from "lucide-react";
import { toast } from "sonner";

const EMPTY = { name: "", email: "", phone: "", address: "" };

function PartyPage({ title, subtitle, path, testidPrefix, showBalance = false }) {
  const [rows, setRows] = useState([]);
  const [q, setQ] = useState("");
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState(EMPTY);
  const [editing, setEditing] = useState(null);

  const load = async () => {
    const { data } = await api.get(`/${path}`, { params: q ? { q } : {} });
    setRows(data);
  };
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [q]);

  const save = async () => {
    if (!form.name) return toast.error("Name is required");
    try {
      if (editing) await api.put(`/${path}/${editing.id}`, form);
      else await api.post(`/${path}`, form);
      toast.success(editing ? "Updated" : "Created");
      setOpen(false); load();
    } catch (e) { toast.error(formatError(e)); }
  };

  const del = (r) => {
    toast(`Delete "${r.name}"?`, {
      action: {
        label: "Delete",
        onClick: async () => {
          try { await api.delete(`/${path}/${r.id}`); toast.success("Deleted"); load(); }
          catch (e) { toast.error(formatError(e)); }
        }
      }
    });
  };

  return (
    <div className="space-y-4" data-testid={`${testidPrefix}-page`}>
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
          <p className="text-sm text-slate-500">{subtitle}</p>
        </div>
        <div className="flex gap-2 items-center">
          <div className="relative">
            <Search className="h-3.5 w-3.5 absolute left-2.5 top-2.5 text-slate-400" />
            <Input data-testid={`${testidPrefix}-search`} placeholder="Search…" value={q} onChange={(e) => setQ(e.target.value)} className="h-9 pl-8 w-64" />
          </div>
          <Button data-testid={`add-${testidPrefix}-btn`} onClick={() => { setEditing(null); setForm(EMPTY); setOpen(true); }} className="h-9 bg-orange-600 hover:bg-orange-700"><Plus className="h-4 w-4 mr-1" />New</Button>
        </div>
      </div>

      <div className="surface overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 border-b border-slate-200">
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-3 py-2">Name</th><th>Email</th><th>Phone</th><th>Address</th>
              {showBalance && <th className="text-right">Balance</th>}
              <th className="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && <tr><td colSpan={showBalance ? 6 : 5} className="py-8 text-center text-slate-500">No records yet.</td></tr>}
            {rows.map((r) => (
              <tr key={r.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="px-3 py-2 font-medium">{r.name}</td>
                <td className="text-slate-600">{r.email || "—"}</td>
                <td className="text-slate-600 font-mono text-xs">{r.phone || "—"}</td>
                <td className="text-slate-500 text-xs max-w-xs truncate">{r.address || "—"}</td>
                {showBalance && <td className={`text-right text-num ${r.balance > 0 ? "text-red-600" : "text-slate-700"}`}>{money(r.balance)}</td>}
                <td className="text-right">
                  <button onClick={() => { setEditing(r); setForm({ ...EMPTY, ...r }); setOpen(true); }} aria-label="Edit" data-testid={`edit-${testidPrefix}-${r.id}`} className="p-1.5 hover:bg-slate-100 rounded"><Pencil className="h-3.5 w-3.5" /></button>
                  <button onClick={() => del(r)} aria-label="Delete" data-testid={`delete-${testidPrefix}-${r.id}`} className="p-1.5 hover:bg-red-50 hover:text-red-600 rounded"><Trash2 className="h-3.5 w-3.5" /></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="md:max-w-lg">
          <DialogHeader><DialogTitle>{editing ? `Edit ${title.slice(0,-1)}` : `New ${title.slice(0,-1)}`}</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <Field label="Name *" value={form.name} onChange={(v) => setForm({ ...form, name: v })} testid={`${testidPrefix}-form-name`} />
            <div className="grid grid-cols-2 gap-3">
              <Field label="Email" value={form.email} onChange={(v) => setForm({ ...form, email: v })} testid={`${testidPrefix}-form-email`} />
              <Field label="Phone" value={form.phone} onChange={(v) => setForm({ ...form, phone: v })} testid={`${testidPrefix}-form-phone`} />
            </div>
            <Field label="Address" value={form.address} onChange={(v) => setForm({ ...form, address: v })} testid={`${testidPrefix}-form-address`} />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button onClick={save} className="bg-orange-600 hover:bg-orange-700" data-testid={`save-${testidPrefix}-btn`}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function Field({ label, value, onChange, testid }) {
  return (
    <div>
      <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">{label}</Label>
      <Input value={value ?? ""} onChange={(e) => onChange(e.target.value)} className="h-9 mt-1" data-testid={testid} />
    </div>
  );
}

export default PartyPage;
