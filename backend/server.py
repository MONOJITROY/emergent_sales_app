from dotenv import load_dotenv
from pathlib import Path

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

import os
import logging
import uuid
from datetime import datetime, timezone, timedelta
from typing import List, Optional, Annotated

import bcrypt
import jwt
from fastapi import FastAPI, APIRouter, HTTPException, Depends, Request, Query
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
from pydantic import BaseModel, Field, EmailStr, ConfigDict


# ---------- Setup ----------
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

JWT_SECRET = os.environ['JWT_SECRET']
JWT_ALG = "HS256"
ACCESS_TOKEN_MIN = 60 * 24  # 1 day

app = FastAPI(title="StockFlow API")
api = APIRouter(prefix="/api")
bearer = HTTPBearer(auto_error=False)

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)


# ---------- Helpers ----------
def now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def new_id() -> str:
    return str(uuid.uuid4())


def hash_pw(p: str) -> str:
    return bcrypt.hashpw(p.encode(), bcrypt.gensalt()).decode()


def check_pw(p: str, h: str) -> bool:
    try:
        return bcrypt.checkpw(p.encode(), h.encode())
    except Exception:
        return False


def make_token(user_id: str, email: str, role: str) -> str:
    payload = {
        "sub": user_id,
        "email": email,
        "role": role,
        "exp": datetime.now(timezone.utc) + timedelta(minutes=ACCESS_TOKEN_MIN),
    }
    return jwt.encode(payload, JWT_SECRET, algorithm=JWT_ALG)


def strip_user(u: dict) -> dict:
    u = dict(u)
    u.pop("password_hash", None)
    u.pop("_id", None)
    return u


async def get_current_user(creds: Optional[HTTPAuthorizationCredentials] = Depends(bearer)) -> dict:
    if not creds:
        raise HTTPException(401, "Not authenticated")
    try:
        payload = jwt.decode(creds.credentials, JWT_SECRET, algorithms=[JWT_ALG])
    except jwt.ExpiredSignatureError:
        raise HTTPException(401, "Token expired")
    except jwt.InvalidTokenError:
        raise HTTPException(401, "Invalid token")
    user = await db.users.find_one({"id": payload["sub"]}, {"_id": 0})
    if not user:
        raise HTTPException(401, "User not found")
    return user


def require_admin(user: dict = Depends(get_current_user)) -> dict:
    if user.get("role") != "admin":
        raise HTTPException(403, "Admin access required")
    return user


# ---------- Models ----------
class UserCreate(BaseModel):
    email: str
    password: str
    name: str
    role: str = "staff"  # admin | staff


class UserUpdate(BaseModel):
    name: Optional[str] = None
    role: Optional[str] = None
    password: Optional[str] = None


class LoginInput(BaseModel):
    email: str
    password: str


class Product(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str = Field(default_factory=new_id)
    sku: str
    name: str
    category: Optional[str] = ""
    unit: str = "pcs"
    cost_price: float = 0.0
    sale_price: float = 0.0
    stock: float = 0.0
    reorder_level: float = 0.0
    description: Optional[str] = ""
    created_at: str = Field(default_factory=now_iso)


class Customer(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str = Field(default_factory=new_id)
    name: str
    email: Optional[str] = ""
    phone: Optional[str] = ""
    address: Optional[str] = ""
    balance: float = 0.0
    created_at: str = Field(default_factory=now_iso)


class Supplier(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str = Field(default_factory=new_id)
    name: str
    email: Optional[str] = ""
    phone: Optional[str] = ""
    address: Optional[str] = ""
    created_at: str = Field(default_factory=now_iso)


class LineItem(BaseModel):
    product_id: str
    sku: str
    name: str
    qty: float
    price: float
    total: float


class Sale(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str = Field(default_factory=new_id)
    invoice_no: str
    customer_id: Optional[str] = None
    customer_name: str
    date: str = Field(default_factory=now_iso)
    items: List[LineItem]
    subtotal: float
    discount: float = 0.0
    tax: float = 0.0
    total: float
    paid: float = 0.0
    balance: float = 0.0
    status: str = "unpaid"  # unpaid | partial | paid
    notes: Optional[str] = ""
    created_by: Optional[str] = None
    created_at: str = Field(default_factory=now_iso)


class SaleInput(BaseModel):
    customer_id: Optional[str] = None
    customer_name: str
    items: List[LineItem]
    discount: float = 0.0
    tax: float = 0.0
    paid: float = 0.0
    notes: Optional[str] = ""


class Purchase(BaseModel):
    model_config = ConfigDict(extra="ignore")
    id: str = Field(default_factory=new_id)
    ref_no: str
    supplier_id: Optional[str] = None
    supplier_name: str
    date: str = Field(default_factory=now_iso)
    items: List[LineItem]
    subtotal: float
    tax: float = 0.0
    total: float
    notes: Optional[str] = ""
    created_by: Optional[str] = None
    created_at: str = Field(default_factory=now_iso)


class PurchaseInput(BaseModel):
    supplier_id: Optional[str] = None
    supplier_name: str
    items: List[LineItem]
    tax: float = 0.0
    notes: Optional[str] = ""


# ---------- Auth Routes ----------
@api.post("/auth/login")
async def login(data: LoginInput):
    user = await db.users.find_one({"email": data.email.lower()}, {"_id": 0})
    if not user or not check_pw(data.password, user["password_hash"]):
        raise HTTPException(401, "Invalid email or password")
    token = make_token(user["id"], user["email"], user["role"])
    return {"token": token, "user": strip_user(user)}


@api.get("/auth/me")
async def me(user: dict = Depends(get_current_user)):
    return strip_user(user)


# ---------- Users (admin) ----------
@api.get("/users")
async def list_users(user: dict = Depends(require_admin)):
    rows = await db.users.find({}, {"_id": 0, "password_hash": 0}).sort("created_at", -1).to_list(500)
    return rows


@api.post("/users")
async def create_user(data: UserCreate, user: dict = Depends(require_admin)):
    email = data.email.lower()
    if await db.users.find_one({"email": email}):
        raise HTTPException(400, "Email already exists")
    doc = {
        "id": new_id(),
        "email": email,
        "name": data.name,
        "role": data.role if data.role in ("admin", "staff") else "staff",
        "password_hash": hash_pw(data.password),
        "created_at": now_iso(),
    }
    await db.users.insert_one(doc)
    return strip_user(doc)


@api.put("/users/{uid}")
async def update_user(uid: str, data: UserUpdate, user: dict = Depends(require_admin)):
    upd = {}
    if data.name is not None:
        upd["name"] = data.name
    if data.role in ("admin", "staff"):
        upd["role"] = data.role
    if data.password:
        upd["password_hash"] = hash_pw(data.password)
    if not upd:
        raise HTTPException(400, "Nothing to update")
    res = await db.users.update_one({"id": uid}, {"$set": upd})
    if res.matched_count == 0:
        raise HTTPException(404, "User not found")
    u = await db.users.find_one({"id": uid}, {"_id": 0, "password_hash": 0})
    return u


@api.delete("/users/{uid}")
async def delete_user(uid: str, user: dict = Depends(require_admin)):
    if uid == user["id"]:
        raise HTTPException(400, "Cannot delete yourself")
    res = await db.users.delete_one({"id": uid})
    if res.deleted_count == 0:
        raise HTTPException(404, "User not found")
    return {"ok": True}


# ---------- Generic CRUD helpers ----------
def crud_routes(prefix: str, collection: str, Model):
    @api.get(f"/{prefix}")
    async def list_items(q: Optional[str] = None, user: dict = Depends(get_current_user)):
        query = {}
        if q:
            query = {"$or": [
                {"name": {"$regex": q, "$options": "i"}},
                {"sku": {"$regex": q, "$options": "i"}},
                {"email": {"$regex": q, "$options": "i"}},
                {"phone": {"$regex": q, "$options": "i"}},
            ]}
        rows = await db[collection].find(query, {"_id": 0}).sort("created_at", -1).to_list(1000)
        return rows

    @api.post(f"/{prefix}")
    async def create_item(data: dict, user: dict = Depends(get_current_user)):
        obj = Model(**data)
        await db[collection].insert_one(obj.model_dump())
        return obj.model_dump()

    @api.get(f"/{prefix}/{{item_id}}")
    async def get_item(item_id: str, user: dict = Depends(get_current_user)):
        row = await db[collection].find_one({"id": item_id}, {"_id": 0})
        if not row:
            raise HTTPException(404, "Not found")
        return row

    @api.put(f"/{prefix}/{{item_id}}")
    async def update_item(item_id: str, data: dict, user: dict = Depends(get_current_user)):
        data.pop("id", None)
        data.pop("created_at", None)
        res = await db[collection].update_one({"id": item_id}, {"$set": data})
        if res.matched_count == 0:
            raise HTTPException(404, "Not found")
        row = await db[collection].find_one({"id": item_id}, {"_id": 0})
        return row

    @api.delete(f"/{prefix}/{{item_id}}")
    async def delete_item(item_id: str, user: dict = Depends(get_current_user)):
        res = await db[collection].delete_one({"id": item_id})
        if res.deleted_count == 0:
            raise HTTPException(404, "Not found")
        return {"ok": True}


crud_routes("products", "products", Product)
crud_routes("customers", "customers", Customer)
crud_routes("suppliers", "suppliers", Supplier)


# ---------- Sales ----------
async def next_seq(name: str, prefix: str) -> str:
    doc = await db.counters.find_one_and_update(
        {"_id": name}, {"$inc": {"seq": 1}}, upsert=True, return_document=True
    )
    seq = doc["seq"] if doc and "seq" in doc else 1
    return f"{prefix}-{seq:05d}"


@api.get("/sales")
async def list_sales(q: Optional[str] = None, user: dict = Depends(get_current_user)):
    query = {}
    if q:
        query = {"$or": [
            {"invoice_no": {"$regex": q, "$options": "i"}},
            {"customer_name": {"$regex": q, "$options": "i"}},
        ]}
    rows = await db.sales.find(query, {"_id": 0}).sort("created_at", -1).to_list(1000)
    return rows


@api.get("/sales/{sid}")
async def get_sale(sid: str, user: dict = Depends(get_current_user)):
    row = await db.sales.find_one({"id": sid}, {"_id": 0})
    if not row:
        raise HTTPException(404, "Not found")
    return row


@api.post("/sales")
async def create_sale(data: SaleInput, user: dict = Depends(get_current_user)):
    if not data.items:
        raise HTTPException(400, "At least one line item is required")
    subtotal = sum(i.total for i in data.items)
    total = max(0.0, subtotal - data.discount + data.tax)
    paid = min(data.paid, total)
    balance = round(total - paid, 2)
    status = "paid" if balance <= 0 else ("partial" if paid > 0 else "unpaid")
    invoice_no = await next_seq("invoice", "INV")
    sale = Sale(
        invoice_no=invoice_no,
        customer_id=data.customer_id,
        customer_name=data.customer_name,
        items=data.items,
        subtotal=round(subtotal, 2),
        discount=data.discount,
        tax=data.tax,
        total=round(total, 2),
        paid=round(paid, 2),
        balance=balance,
        status=status,
        notes=data.notes,
        created_by=user["id"],
    )
    # Decrement stock
    for it in data.items:
        await db.products.update_one({"id": it.product_id}, {"$inc": {"stock": -it.qty}})
    # Update customer balance
    if data.customer_id and balance > 0:
        await db.customers.update_one({"id": data.customer_id}, {"$inc": {"balance": balance}})
    await db.sales.insert_one(sale.model_dump())
    return sale.model_dump()


@api.post("/sales/{sid}/payment")
async def record_payment(sid: str, payload: dict, user: dict = Depends(get_current_user)):
    amount = float(payload.get("amount", 0))
    if amount <= 0:
        raise HTTPException(400, "Amount must be positive")
    sale = await db.sales.find_one({"id": sid}, {"_id": 0})
    if not sale:
        raise HTTPException(404, "Sale not found")
    new_paid = min(sale["total"], sale["paid"] + amount)
    new_balance = round(sale["total"] - new_paid, 2)
    new_status = "paid" if new_balance <= 0 else ("partial" if new_paid > 0 else "unpaid")
    await db.sales.update_one({"id": sid}, {"$set": {"paid": new_paid, "balance": new_balance, "status": new_status}})
    if sale.get("customer_id"):
        await db.customers.update_one({"id": sale["customer_id"]}, {"$inc": {"balance": -(new_paid - sale["paid"])}})
    return await db.sales.find_one({"id": sid}, {"_id": 0})


@api.delete("/sales/{sid}")
async def delete_sale(sid: str, user: dict = Depends(get_current_user)):
    sale = await db.sales.find_one({"id": sid}, {"_id": 0})
    if not sale:
        raise HTTPException(404, "Not found")
    # restore stock
    for it in sale.get("items", []):
        await db.products.update_one({"id": it["product_id"]}, {"$inc": {"stock": it["qty"]}})
    # reverse customer balance
    if sale.get("customer_id") and sale.get("balance", 0) > 0:
        await db.customers.update_one({"id": sale["customer_id"]}, {"$inc": {"balance": -sale["balance"]}})
    await db.sales.delete_one({"id": sid})
    return {"ok": True}


# ---------- Purchases ----------
@api.get("/purchases")
async def list_purchases(q: Optional[str] = None, user: dict = Depends(get_current_user)):
    query = {}
    if q:
        query = {"$or": [
            {"ref_no": {"$regex": q, "$options": "i"}},
            {"supplier_name": {"$regex": q, "$options": "i"}},
        ]}
    rows = await db.purchases.find(query, {"_id": 0}).sort("created_at", -1).to_list(1000)
    return rows


@api.post("/purchases")
async def create_purchase(data: PurchaseInput, user: dict = Depends(get_current_user)):
    if not data.items:
        raise HTTPException(400, "At least one line item is required")
    subtotal = sum(i.total for i in data.items)
    total = round(subtotal + data.tax, 2)
    ref_no = await next_seq("purchase", "PUR")
    p = Purchase(
        ref_no=ref_no,
        supplier_id=data.supplier_id,
        supplier_name=data.supplier_name,
        items=data.items,
        subtotal=round(subtotal, 2),
        tax=data.tax,
        total=total,
        notes=data.notes,
        created_by=user["id"],
    )
    # Increment stock + update cost price
    for it in data.items:
        await db.products.update_one(
            {"id": it.product_id},
            {"$inc": {"stock": it.qty}, "$set": {"cost_price": it.price}},
        )
    await db.purchases.insert_one(p.model_dump())
    return p.model_dump()


@api.delete("/purchases/{pid}")
async def delete_purchase(pid: str, user: dict = Depends(get_current_user)):
    p = await db.purchases.find_one({"id": pid}, {"_id": 0})
    if not p:
        raise HTTPException(404, "Not found")
    for it in p.get("items", []):
        await db.products.update_one({"id": it["product_id"]}, {"$inc": {"stock": -it["qty"]}})
    await db.purchases.delete_one({"id": pid})
    return {"ok": True}


# ---------- Dashboard ----------
@api.get("/dashboard/stats")
async def dashboard_stats(user: dict = Depends(get_current_user)):
    today = datetime.now(timezone.utc).date().isoformat()

    sales = await db.sales.find({}, {"_id": 0}).to_list(5000)
    products = await db.products.find({}, {"_id": 0}).to_list(2000)
    customers_count = await db.customers.count_documents({})

    today_sales = sum(s["total"] for s in sales if s["date"].startswith(today))
    total_sales = sum(s["total"] for s in sales)
    outstanding = sum(s.get("balance", 0) for s in sales)
    low_stock = [p for p in products if p["stock"] <= p.get("reorder_level", 0) and p.get("reorder_level", 0) > 0]

    # 7-day sales series
    series = {}
    for i in range(7):
        d = (datetime.now(timezone.utc) - timedelta(days=6 - i)).date().isoformat()
        series[d] = 0.0
    for s in sales:
        d = s["date"][:10]
        if d in series:
            series[d] += s["total"]
    chart = [{"date": k, "total": round(v, 2)} for k, v in series.items()]

    recent_sales = sorted(sales, key=lambda x: x["created_at"], reverse=True)[:5]
    for r in recent_sales:
        r.pop("items", None)

    return {
        "today_sales": round(today_sales, 2),
        "total_sales": round(total_sales, 2),
        "outstanding": round(outstanding, 2),
        "products_count": len(products),
        "low_stock_count": len(low_stock),
        "customers_count": customers_count,
        "chart": chart,
        "recent_sales": recent_sales,
        "low_stock_items": low_stock[:10],
    }


# ---------- Reports ----------
@api.get("/reports/sales-by-customer")
async def report_sales_by_customer(user: dict = Depends(get_current_user)):
    sales = await db.sales.find({}, {"_id": 0}).to_list(5000)
    agg = {}
    for s in sales:
        key = s.get("customer_name", "Walk-in")
        if key not in agg:
            agg[key] = {"customer": key, "invoices": 0, "total": 0.0, "balance": 0.0}
        agg[key]["invoices"] += 1
        agg[key]["total"] += s["total"]
        agg[key]["balance"] += s.get("balance", 0)
    rows = list(agg.values())
    rows.sort(key=lambda x: x["total"], reverse=True)
    for r in rows:
        r["total"] = round(r["total"], 2)
        r["balance"] = round(r["balance"], 2)
    return rows


@api.get("/reports/sales-by-product")
async def report_sales_by_product(user: dict = Depends(get_current_user)):
    sales = await db.sales.find({}, {"_id": 0}).to_list(5000)
    agg = {}
    for s in sales:
        for it in s.get("items", []):
            key = it["product_id"]
            if key not in agg:
                agg[key] = {"product": it["name"], "sku": it["sku"], "qty": 0.0, "total": 0.0}
            agg[key]["qty"] += it["qty"]
            agg[key]["total"] += it["total"]
    rows = list(agg.values())
    rows.sort(key=lambda x: x["total"], reverse=True)
    for r in rows:
        r["total"] = round(r["total"], 2)
    return rows


@api.get("/reports/invoice-aging")
async def report_invoice_aging(user: dict = Depends(get_current_user)):
    sales = await db.sales.find({"balance": {"$gt": 0}}, {"_id": 0}).to_list(5000)
    today = datetime.now(timezone.utc)
    buckets = {"0-30": 0.0, "31-60": 0.0, "61-90": 0.0, "90+": 0.0}
    rows = []
    for s in sales:
        try:
            d = datetime.fromisoformat(s["date"].replace("Z", "+00:00"))
        except Exception:
            d = today
        days = (today - d).days
        bucket = "0-30" if days <= 30 else "31-60" if days <= 60 else "61-90" if days <= 90 else "90+"
        buckets[bucket] += s["balance"]
        rows.append({
            "invoice_no": s["invoice_no"],
            "customer": s["customer_name"],
            "date": s["date"][:10],
            "days_overdue": days,
            "total": s["total"],
            "balance": s["balance"],
            "bucket": bucket,
        })
    rows.sort(key=lambda x: x["days_overdue"], reverse=True)
    return {"buckets": {k: round(v, 2) for k, v in buckets.items()}, "rows": rows}


# ---------- Startup ----------
@app.on_event("startup")
async def on_startup():
    # Indexes
    await db.users.create_index("email", unique=True)
    await db.users.create_index("id", unique=True)
    for coll in ("products", "customers", "suppliers", "sales", "purchases"):
        await db[coll].create_index("id", unique=True)
    await db.products.create_index("sku")

    # Seed admin
    admin_email = os.environ.get("ADMIN_EMAIL", "admin@stockflow.local").lower()
    admin_pw = os.environ.get("ADMIN_PASSWORD", "admin123")
    existing = await db.users.find_one({"email": admin_email})
    if not existing:
        await db.users.insert_one({
            "id": new_id(),
            "email": admin_email,
            "name": "Administrator",
            "role": "admin",
            "password_hash": hash_pw(admin_pw),
            "created_at": now_iso(),
        })
        logger.info(f"Seeded admin user: {admin_email}")
    else:
        # keep password in sync with env
        if not check_pw(admin_pw, existing["password_hash"]):
            await db.users.update_one({"email": admin_email}, {"$set": {"password_hash": hash_pw(admin_pw)}})
            logger.info("Updated admin password from env")


@app.on_event("shutdown")
async def shutdown():
    client.close()


app.include_router(api)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)
