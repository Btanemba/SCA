{{-- This file is used for menu items by any Backpack v7 theme --}}
@php
	$currentRoleCode = backpack_user()?->person?->sacRole?->code;
	$isSecurity = $currentRoleCode === \App\Models\Person::ROLE_SECURITY;
	$isAccountant = $currentRoleCode === \App\Models\Person::ROLE_ACCOUNTANT;
	$currentPerson = backpack_user()?->person;
	$hasPayslips = $currentPerson && \App\Models\PayrollEntry::query()
		->where('person_id', $currentPerson->id)
		->whereHas('payroll', fn ($query) => $query->whereIn('status', ['approved', 'paid']))
		->exists();
@endphp
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
@if ($isSecurity)
@elseif ($isAccountant)
	<x-backpack::menu-dropdown title="People" icon="la la-users" :open="request()->routeIs('person.*')">
		<x-backpack::menu-dropdown-item
			title="Staffs"
			:link="backpack_url('person?role=' . \App\Models\Person::ROLE_STAFF)"
			:class="request()->query('role') === \App\Models\Person::ROLE_STAFF ? 'active' : ''"
		/>
	</x-backpack::menu-dropdown>
@else
	<li class="nav-item"><a class="nav-link" href="{{ backpack_url('sac-role') }}"><i class="la la-user-tag nav-icon"></i> Roles</a></li>
	@php
		$peopleRoles = \App\Models\SacRole::query()
			->orderBy('order')
			->orderBy('name')
			->get(['code', 'name']);
	@endphp
	<x-backpack::menu-dropdown title="People" icon="la la-users" :open="request()->routeIs('person.*')">
		@foreach ($peopleRoles as $role)
			<x-backpack::menu-dropdown-item
				:title="$role->name"
				:link="backpack_url('person?role=' . $role->code)"
				:class="request()->query('role') === $role->code ? 'active' : ''"
			/>
		@endforeach
	</x-backpack::menu-dropdown>
	<x-backpack::menu-dropdown title="Jobs" icon="la la-briefcase" :open="request()->is(config('backpack.base.route_prefix').'/job-*')">
		<x-backpack::menu-dropdown-item title="Job openings" :link="backpack_url('job-opening')" icon="la la-bullhorn" />
		<x-backpack::menu-dropdown-item title="Applications" :link="backpack_url('job-application')" icon="la la-inbox" />
	</x-backpack::menu-dropdown>
	@if (in_array($currentRoleCode, [\App\Models\Person::ROLE_STAFF, \App\Models\Person::ROLE_ADMIN, \App\Models\Person::ROLE_FOUNDER], true))
		<li class="nav-item"><a class="nav-link" href="{{ backpack_url('event') }}"><i class="la la-calendar nav-icon"></i> Events</a></li>
	@endif
    @if (in_array($currentRoleCode, [\App\Models\Person::ROLE_ADMIN, \App\Models\Person::ROLE_FOUNDER], true))
	    <x-backpack::menu-dropdown title="Academy" icon="la la-school" :open="request()->routeIs('management-member.*') || request()->routeIs('staff-member.*') || request()->routeIs('academy.settings*') || request()->routeIs('staff-documents.*')">
			<x-backpack::menu-dropdown-item title="Management" :link="backpack_url('management-member')" icon="la la-users-cog" />
			<x-backpack::menu-dropdown-item title="Staff Profiles" :link="backpack_url('staff-member')" icon="la la-user-friends" />
			<x-backpack::menu-dropdown-item title="Academy Settings" :link="route('academy.settings')" icon="la la-school" />
			@if ($currentRoleCode === \App\Models\Person::ROLE_FOUNDER)
				<x-backpack::menu-dropdown-item title="Staff Documents" :link="route('staff-documents.index')" icon="la la-file-signature" />
			@endif
		</x-backpack::menu-dropdown>
    @endif
    <li class="nav-item"><a class="nav-link" href="{{ backpack_url('attendance') }}"><i class="la la-clipboard-check nav-icon"></i> Attendance</a></li>
@endif
@if (in_array($currentRoleCode, [\App\Models\Person::ROLE_ADMIN, \App\Models\Person::ROLE_ACCOUNTANT, \App\Models\Person::ROLE_FOUNDER], true))
	<x-backpack::menu-dropdown title="Finance" icon="la la-calculator" :open="request()->routeIs('payroll.*') || request()->routeIs('expenses.*') || request()->routeIs('academy-fee.*')">
		@if (in_array($currentRoleCode, [\App\Models\Person::ROLE_ACCOUNTANT, \App\Models\Person::ROLE_FOUNDER], true))
			<x-backpack::menu-dropdown-item title="Payroll" :link="route('payroll.index')" icon="la la-money-bill" />
			<x-backpack::menu-dropdown-item title="Expenses" :link="route('expenses.index')" icon="la la-file-invoice-dollar" />
			@if ($hasPayslips)
				<x-backpack::menu-dropdown-item title="My Payslips" :link="route('payroll.mine')" icon="la la-file-invoice-dollar" />
			@endif
		@endif
		@if (in_array($currentRoleCode, [\App\Models\Person::ROLE_ADMIN, \App\Models\Person::ROLE_FOUNDER], true))
			<x-backpack::menu-dropdown-item title="Fees" :link="backpack_url('academy-fee')" icon="la la-money-bill-wave" />
		@endif
	</x-backpack::menu-dropdown>
@endif
@if ($currentPerson && $currentRoleCode && ! in_array($currentRoleCode, [\App\Models\Person::ROLE_STUDENT, \App\Models\Person::ROLE_FOUNDER], true))
	<li class="nav-item"><a class="nav-link" href="{{ route('staff-documents.mine') }}"><i class="la la-file-alt nav-icon"></i> My Documents</a></li>
@endif
@if ($hasPayslips && ! in_array($currentRoleCode, [\App\Models\Person::ROLE_ACCOUNTANT, \App\Models\Person::ROLE_FOUNDER], true))
	<li class="nav-item"><a class="nav-link" href="{{ route('payroll.mine') }}"><i class="la la-file-invoice-dollar nav-icon"></i> My Payslips</a></li>
@endif

@if (in_array($currentRoleCode, [\App\Models\Person::ROLE_ADMIN, \App\Models\Person::ROLE_ACCOUNTANT, \App\Models\Person::ROLE_FOUNDER], true))
	<x-backpack::menu-dropdown title="Mails" icon="la la-envelope">
		@if (in_array($currentRoleCode, [\App\Models\Person::ROLE_ADMIN, \App\Models\Person::ROLE_FOUNDER], true))
			<x-backpack::menu-dropdown-item
				title="Info Mail "
				:link="route('mail-accounts.show', 'info')"
				icon="la la-key"
			/>
		@endif
		<x-backpack::menu-dropdown-item
			title="Finance Mail "
			:link="route('mail-accounts.show', 'finance')"
			icon="la la-key"
		/>
	</x-backpack::menu-dropdown>
@endif
<li class="nav-item"><a class="nav-link text-danger" href="{{ route('backpack.logout.home') }}"><i class="la la-sign-out-alt nav-icon"></i> {{ trans('backpack::base.logout') }}</a></li>
