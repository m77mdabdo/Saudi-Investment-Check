<x-layouts.admin title="Users">
    <x-admin.page-header title="Users & Roles" subtitle="Admin يقدر يعدّل كل حاجة، Manager يدير المحتوى، Sales يشتغل على الـleads."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Users' => null]" />

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="ad-card p-5">
            <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">مستخدم جديد</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">
                @csrf
                <div><label class="ad-label" for="name">الاسم</label><input id="name" name="name" class="ad-input" required></div>
                <div><label class="ad-label" for="email">البريد</label><input id="email" type="email" name="email" class="ad-input" required dir="ltr"></div>
                <div>
                    <label class="ad-label" for="role">الدور</label>
                    <select id="role" name="role" class="ad-select">
                        @foreach (\App\Models\User::ROLES as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="ad-label" for="password">كلمة المرور</label><input id="password" type="password" name="password" class="ad-input" required></div>
                <div><label class="ad-label" for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" type="password" name="password_confirmation" class="ad-input" required></div>
                <button class="ad-btn ad-btn-primary w-full">إنشاء</button>
                @error('password')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                @error('email')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            </form>
        </section>

        <section class="ad-card overflow-hidden lg:col-span-2">
            <div class="ad-table-wrap"><table class="ad-table">
                <thead><tr><th>المستخدم</th><th>الدور</th><th>Leads</th><th>آخر دخول</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td colspan="5" class="p-0">
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="ad-inline-form px-3 py-2.5">
                                    @csrf @method('PUT')
                                    <input name="name" class="ad-input h-9 min-h-9 w-36 py-1 text-sm" value="{{ $user->name }}">
                                    <input name="email" class="ad-input h-9 min-h-9 w-52 py-1 text-sm" dir="ltr" value="{{ $user->email }}">
                                    <select name="role" class="ad-select h-9 min-h-9 w-auto py-1 text-xs">
                                        @foreach (\App\Models\User::ROLES as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                                        @endforeach
                                    </select>
                                    <input type="password" name="password" class="ad-input h-9 min-h-9 w-36 py-1 text-xs" placeholder="كلمة مرور جديدة" autocomplete="new-password">
                                    <input type="password" name="password_confirmation" class="ad-input h-9 min-h-9 w-36 py-1 text-xs" placeholder="تأكيد" autocomplete="new-password">
                                    <label class="ad-check"><input type="checkbox" name="is_active" value="1" class="accent-[#d9a742]" @checked($user->is_active)> نشط</label>
                                    <span class="text-xs text-slate-400">{{ $user->assigned_leads_count }} leads · {{ $user->last_login_at?->diffForHumans() ?? 'لم يدخل' }}</span>
                                    <button class="ad-btn ad-btn-dark ms-auto">حفظ</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    </div>
</x-layouts.admin>
