import { useState } from "react";
import { useNavigate, Navigate } from "react-router-dom";
import { useAuth } from "@/contexts/AuthContext";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { formatError } from "@/lib/api";
import { Boxes } from "lucide-react";

export default function Login() {
  const { user, login } = useAuth();
  const navigate = useNavigate();
  const [email, setEmail] = useState("admin@stockflow.local");
  const [password, setPassword] = useState("admin123");
  const [loading, setLoading] = useState(false);

  if (user) return <Navigate to="/" replace />;

  const submit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      await login(email, password);
      toast.success("Signed in");
      navigate("/");
    } catch (err) {
      toast.error(formatError(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen grid lg:grid-cols-2">
      {/* Left form */}
      <div className="flex items-center justify-center p-8 bg-slate-50">
        <div className="w-full max-w-sm">
          <div className="flex items-center gap-2 mb-8">
            <div className="h-8 w-8 bg-orange-600 rounded-sm grid place-items-center text-white text-xs font-bold">SF</div>
            <div>
              <div className="font-bold text-lg leading-none" style={{fontFamily:"Chivo"}}>StockFlow</div>
              <div className="text-[11px] text-slate-500 uppercase tracking-wider">Sales & Inventory</div>
            </div>
          </div>
          <h1 className="text-2xl font-bold tracking-tight mb-1">Sign in to your workspace</h1>
          <p className="text-sm text-slate-500 mb-6">Use the admin credentials below to explore the demo.</p>

          <form onSubmit={submit} className="space-y-3" data-testid="login-form">
            <div>
              <Label htmlFor="email" className="text-xs font-semibold uppercase tracking-wider text-slate-600">Email</Label>
              <Input
                id="email" type="email" autoComplete="email" required
                value={email} onChange={(e) => setEmail(e.target.value)}
                className="h-9 mt-1" data-testid="login-email-input"
              />
            </div>
            <div>
              <Label htmlFor="password" className="text-xs font-semibold uppercase tracking-wider text-slate-600">Password</Label>
              <Input
                id="password" type="password" autoComplete="current-password" required
                value={password} onChange={(e) => setPassword(e.target.value)}
                className="h-9 mt-1" data-testid="login-password-input"
              />
            </div>
            <Button
              type="submit" disabled={loading}
              className="w-full h-10 bg-orange-600 hover:bg-orange-700 text-white font-medium"
              data-testid="login-submit-button"
            >
              {loading ? "Signing in…" : "Sign in"}
            </Button>
          </form>

          <div className="mt-6 p-3 border border-slate-200 rounded-md bg-white text-xs text-slate-600">
            <div className="label-tiny mb-1">Demo credentials</div>
            <div className="font-mono">admin@stockflow.local / admin123</div>
          </div>
        </div>
      </div>

      {/* Right image */}
      <div className="hidden lg:block relative">
        <img
          src="https://images.unsplash.com/photo-1771530789155-b1f03fbf82b5?crop=entropy&cs=srgb&fm=jpg&ixid=M3w4NjAzNDR8MHwxfHNlYXJjaHwxfHxtaW5pbWFsaXN0JTIwd2FyZWhvdXNlJTIwaW50ZXJpb3J8ZW58MHx8fHwxNzgyNTgzODc2fDA&ixlib=rb-4.1.0&q=85"
          alt="warehouse"
          className="absolute inset-0 w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-slate-900/40 mix-blend-multiply" />
        <div className="relative h-full flex flex-col justify-end p-10 text-white">
          <div className="flex items-center gap-2 mb-2">
            <Boxes className="h-5 w-5 text-orange-400" />
            <div className="label-tiny text-orange-300">Operations · Inventory · Sales</div>
          </div>
          <h2 className="text-4xl font-black tracking-tight max-w-md leading-tight" style={{fontFamily:"Chivo"}}>
            Move stock. Close sales. Stay in control.
          </h2>
          <p className="text-sm text-slate-200 mt-3 max-w-md">A compact, no-nonsense workspace for small teams running real-world inventory.</p>
        </div>
      </div>
    </div>
  );
}
