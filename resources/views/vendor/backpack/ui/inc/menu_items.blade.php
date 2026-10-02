{{-- This file is used for menu items by any Backpack v7 theme --}}
@php
	$currentRoleCode = backpack_user()?->person?->sacRole?->code;
	$isSecurity = $currentRoleCode === \App\Models\Person::ROLE_SECURITY;
	$isAccountant = $currentRoleCode === \App\Models\Person::ROLE_ACCOUNTANT;
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
    @if (in_array($currentRoleCode, [\App\Models\Person::ROLE_ADMIN, \App\Models\Person::ROLE_FOUNDER], true))
	    <li class="nav-item"><a class="nav-link" href="{{ backpack_url('management-member') }}"><i class="la la-users-cog nav-icon"></i> Management</a></li>
    @endif
    <li class="nav-item"><a class="nav-link" href="{{ backpack_url('attendance') }}"><i class="la la-clipboard-check nav-icon"></i> Attendance</a></li>
@endif
<li class="nav-item"><a class="nav-link text-danger" href="{{ backpack_url('logout') }}"><i class="la la-sign-out-alt nav-icon"></i> {{ trans('backpack::base.logout') }}</a></li>
