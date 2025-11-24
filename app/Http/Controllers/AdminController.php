<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\Provider;
use App\Models\InvoiceRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PaymentReceiptMail;
use App\Notifications\OrderStatusNotification;

class AdminController extends Controller
{
    public function usersIndex(Request $request)
    {
        $role = $request->query('role');
        $query = User::orderBy('id', 'asc');
        if ($role && in_array($role, ['customer','provider','admin'], true)) {
            $query->where('role', $role);
        }
        $users = $query->get();
        return view('admin.users', compact('users', 'role'));
    }

    public function usersUpdateRole(Request $request, User $user)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }
        $request->validate([
            'role' => 'required|string|in:customer,provider,admin',
        ]);
        $user->role = $request->input('role');
        $user->save();
        if ($user->role === 'provider' && !$user->provider) {
            Provider::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'contact' => '',
                'logo_url' => null,
                'location' => '',
                'description' => '',
            ]);
        }
        return redirect()->route('admin.users')->with('success', 'Rol actualizado');
    }

    public function usersEdit(Request $request, User $user)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }
        return view('admin.users_form', [
            'user' => $user,
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
            'title' => 'Editar usuario',
        ]);
    }

    public function usersUpdate(Request $request, User $user)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|string|in:customer,provider,admin',
        ]);
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->role = $request->input('role');
        $user->save();

        if ($user->role === 'provider' && !$user->provider) {
            Provider::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'contact' => $user->email,
                'logo_url' => null,
                'location' => '',
                'description' => '',
            ]);
        }

        return redirect()->route('admin.users')->with('success', 'Usuario actualizado');
    }

    public function ordersIndex(Request $request)
    {
        $orders = Order::latest()->get();
        return view('admin.orders', compact('orders'));
    }

    public function ordersUpdateStatus(Request $request, Order $order)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }
        $request->validate([
            'status' => 'required|string|in:pending,paid,cancelled,refund_requested,refunded,paused',
        ]);
        $order->status = $request->input('status');
        $order->save();
        try {
            $user = User::find($order->user_id);
            if ($user) {
                $title = 'Estado actualizado';
                $message = 'Tu pedido cambió a "'.ucfirst($order->status).'".';
                $user->notify(new OrderStatusNotification($order, $title, $message));
            }
        } catch (\Throwable $e) {
            Log::warning('Notif admin status: '.$e->getMessage());
        }
        if ($order->status === 'paid') {
            try {
                $user = User::find($order->user_id);
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new PaymentReceiptMail($order));
                }
                $inv = InvoiceRequest::where('order_id', $order->id)->latest()->first();
                if ($inv && $inv->email) {
                    Mail::to($inv->email)->send(new PaymentReceiptMail($order));
                }
            } catch (\Throwable $e) {
                Log::warning('Error enviando comprobante (admin): '.$e->getMessage());
            }
        }
        return redirect()->route('admin.orders')->with('success', 'Estado actualizado');
    }

    public function providersIndex(Request $request)
    {
        $providers = Provider::with('user')->orderBy('id', 'asc')->get();
        return view('admin.providers.index', compact('providers'));
    }

    public function providersCreate(Request $request)
    {
        $users = User::orderBy('name')->get();
        return view('admin.providers.form', [
            'provider' => null,
            'users' => $users,
            'action' => route('admin.providers.store'),
            'method' => 'POST',
            'title' => 'Crear proveedor',
        ]);
    }

    public function providersStore(Request $request)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $user = User::findOrFail((int) $request->input('user_id'));
        if ($user->role !== 'provider') {
            $user->role = 'provider';
            $user->save();
        }

        $logoUrl = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'provider_logos/'.($user->id).'-'.uniqid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('', $filename, 'public');
            $logoUrl = \Illuminate\Support\Facades\Storage::url($path);
        }

        Provider::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'contact' => (string) $request->input('contact'),
            'logo_url' => $logoUrl,
            'location' => $request->input('location'),
            'description' => (string) $request->input('description'),
        ]);

        return redirect()->route('admin.providers')->with('success', 'Proveedor creado');
    }

    public function providersEdit(Request $request, Provider $provider)
    {
        $users = User::orderBy('name')->get();
        return view('admin.providers.form', [
            'provider' => $provider,
            'users' => $users,
            'action' => route('admin.providers.update', $provider),
            'method' => 'PUT',
            'title' => 'Editar proveedor',
        ]);
    }

    public function providersUpdate(Request $request, Provider $provider)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $user = User::findOrFail((int) $request->input('user_id'));
        if ($user->role !== 'provider') {
            $user->role = 'provider';
            $user->save();
        }

        $data = [
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'contact' => (string) $request->input('contact'),
            'location' => $request->input('location'),
            'description' => (string) $request->input('description'),
        ];

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'provider_logos/'.($user->id).'-'.uniqid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('', $filename, 'public');
            $data['logo_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        $provider->update($data);

        return redirect()->route('admin.providers')->with('success', 'Proveedor actualizado');
    }

    public function providersDestroy(Request $request, Provider $provider)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403);
        }
        $provider->delete();
        return redirect()->route('admin.providers')->with('success', 'Proveedor eliminado');
    }
}
