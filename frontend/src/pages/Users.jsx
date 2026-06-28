import { useEffect, useState } from "react";
import { api, formatError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";

const EMPTY = { name: "", email: "", password: "", role: "staff" };

export default function Users() {
  const [rows, setRows] = useState([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState(EMPTY);

  const load = () => api.get("/users").then((r) => setRows(r.data));
  useEffect(() => { load(); }, []);

  const save = async () => {
    if (!form.email || !form.password || !form.name) return toast.error("Name, email, password required");
    try {
      await api.post("/users", form);
      toast.success("User created");
      setOpen(false); setForm(EMPTY); load();
    } catch (e) { toast.error(formatError(e)); }
  };

  const del = (r) => {
    toast(`Delete user ${r.email}?`, {
      action: { label: "Delete", onClick: async () => {
        try { await api.delete(`/users/${r.id}`); toast.success("Deleted"); load(); }
        catch (e) { toast.error(formatError(e)); }
      }}
    });
  };

  return (
    <div className="space-y-4" data-testid="users-page">
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Users & Roles</h1>
          <p className="text-sm text-slate-500">Manage staff accounts (admin only).</p>
        </div>
        <Button onClick={() => { setForm(EMPTY); setOpen(true); }} className="h-9 bg-orange-600 hover:bg-orange-700" data-testid="add-user-btn"><Plus className="h-4 w-4 mr-1" />New User</Button>
      </div>

      <div className="surface overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 border-b border-slate-200">
            <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-3 py-2">Name</th><th>Email</th><th>Role</th><th>Created</th><th className="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="px-3 py-2 font-medium">{r.name}</td>
                <td className="text-slate-700">{r.email}</td>
                <td><span className={`text-[11px] uppercase font-semibold border rounded px-1.5 py-0.5 ${r.role === "admin" ? "bg-orange-50 text-orange-700 border-orange-200" : "bg-slate-50 text-slate-700 border-slate-200"}`}>{r.role}</span></td>
                <td className="text-xs text-slate-500">{r.created_at?.slice(0,10)}</td>
                <td className="text-right">
                  <button onClick={() => del(r)} aria-label="Delete" className="p-1.5 hover:bg-red-50 hover:text-red-600 rounded"><Trash2 className="h-3.5 w-3.5" /></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="md:max-w-md">
          <DialogHeader><DialogTitle>New user</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div>
              <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Name</Label>
              <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="h-9 mt-1" data-testid="user-name-input" />
            </div>
            <div>
              <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Email</Label>
              <Input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="h-9 mt-1" data-testid="user-email-input" />
            </div>
            <div>
              <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Password</Label>
              <Input type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className="h-9 mt-1" data-testid="user-password-input" />
            </div>
            <div>
              <Label className="text-xs font-semibold uppercase tracking-wider text-slate-600">Role</Label>
              <Select value={form.role} onValueChange={(v) => setForm({ ...form, role: v })}>
                <SelectTrigger className="h-9 mt-1" data-testid="user-role-select"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="admin">Admin</SelectItem>
                  <SelectItem value="staff">Staff</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button onClick={save} className="bg-orange-600 hover:bg-orange-700" data-testid="save-user-btn">Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
