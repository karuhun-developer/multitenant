<div>
    <x-slot:page-title>
        {{ $title ?? '' }}
    </x-slot:page-title>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{ $title ?? '' }} Data</h5>
        </div>
        <div class="card-body">
            <x-acc-header :$originRoute />
            <div class="table-responsive">
                <x-acc-table>
                    <thead>
                        <tr>
                            <x-acc-loop-th :$searchBy :$orderBy :$order />
                            <th>
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($get as $d)
                            <tr>
                                <td>{{ $d->name }}</td>
                                <td>{{ $d->domain }}</td>
                                <td>{{ $d->owner_name }}</td>
                                <td>{{ $d->owner_email }}</td>
                                <td>{{ $d->created_at->format('d F Y') }}</td>
                                <x-acc-update-delete :id="$d->id" :$originRoute>
                                    <button class="dropdown-item"
                                        wire:click="editPassword('{{ $d->id }}')">
                                        <i class="fa fa-key"></i>
                                        <span class="ms-2">
                                            Change Password
                                        </span>
                                    </button>
                                    <a class="dropdown-item"
                                        href="{{ route('cms.tenant.user', [
                                            'tenant' => $d->id,
                                        ]) }}">
                                        <i class="fa fa-users"></i>
                                        <span class="ms-2">
                                            Users
                                        </span>
                                    </a>
                                </x-acc-update-delete>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100" class="text-center">
                                    No Data Found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-acc-table>
            </div>
            <div class="float-end">
                {{ $get->links() }}
            </div>
        </div>
    </div>

    {{-- Create / Update Modal --}}
    <x-acc-modal title="{{ $isUpdate ? 'Update' : 'Create' }} {{ $title }}" modal="acc-modal">
        <x-acc-form submit="save">
            <div class="col-md-12">
                <h4>Tenant Information</h4>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Tenant Name <x-acc-required /></label>
                    <x-acc-input model="form.tenant_name" placeholder="Name" icon="fa fa-building" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Subdomain <x-acc-required /></label>
                    <x-acc-input model="form.tenant_domain" placeholder="Domain" icon="fa fa-globe" />
                </div>
            </div>
            <div class="col-md-12">
                <h4>Account Information</h4>
            </div>
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label">Tenant Username <x-acc-required /></label>
                    <x-acc-input model="form.user_name" placeholder="Name" icon="fa fa-user" />
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label">Tenant Email <x-acc-required /></label>
                    <x-acc-input type="email" model="form.user_email" placeholder="Email" icon="fa fa-envelope" />
                </div>
            </div>
            @if(!$isUpdate)
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Tenant Password <x-acc-required /></label>
                        <x-acc-input type="password" model="form.user_password" placeholder="********" icon="fa fa-key" />
                    </div>
                </div>
            @endif
        </x-acc-form>
    </x-acc-modal>

    {{-- Change password --}}
    <x-acc-modal title="Change Password {{ $form->user_name }}" modal="updatePasswordModal">
        <x-acc-form submit="changePassword">
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label">Password <x-acc-required /></label>
                    <x-acc-input type="password" model="form.password" placeholder="********" icon="fa fa-key" />
                </div>
            </div>
        </x-acc-form>
    </x-acc-modal>
</div>
