<script lang="ts">
	import { preventDefault } from 'svelte/legacy';

	import { _ } from '$lib/locale';
	import { system } from '$lib/sys';
	import { loginUser } from '$lib/user';
	import Logo from '$shell/Logo.svelte';
	import Button from '$shell/Button.svelte';
	import IcoLogin from '$shell/icons/IcoLogin.svelte';

	type Props = {
		message?: string;
	};

	let { message = $bindable('') }: Props = $props();

	async function handleSubmit() {
		const data = new FormData(this);
		const login = data.get('login');
		const password = data.get('password');
		let rememberme = false;

		if (data.get('rememberme') === 'true') {
			rememberme = true;
		}

		if (!login || !password) {
			message = _('Please provide username and password');
			return;
		}

		let result = await loginUser(login, password, rememberme);

		if (result !== true) {
			message = result;
		}
	}
</script>

<svelte:head>
	{#each $system.theme as href (href)}
		<link
			rel="stylesheet"
			{href} />
	{/each}
</svelte:head>

<div class="cms-login">
	{#if $system.initialized}
		{#if $system.logo}
			<div class="cms-login-logo-wrap">
				<div class="cms-login-logo-box">
					<img
						style="width: 10rem; display: block; margin: 0 auto;"
						src={$system.logo}
						alt="Panel Logo" />
				</div>
			</div>
		{:else}
			<div class="cms-login-logo-wrap">
				<div class="cms-login-logo-box cms-login-logo-fallback">
					<Logo />
				</div>
			</div>
		{/if}
	{/if}

	{#if message}
		<div class="cms-login-message">
			{message}
		</div>
	{/if}

	<div class="cms-login-card-wrap">
		<div class="cms-login-card">
			<form
				method="POST"
				onsubmit={preventDefault(handleSubmit)}
				class="cms-login-form">
				<div class="cms-login-field">
					<label
						for="login"
						class="cms-field-label">
						{_('Benutzername oder E-Mail-Adresse')}
					</label>
					<div class="cms-field-control">
						<input
							id="login"
							name="login"
							type="text"
							autocomplete="username"
							required
							class="cms-input" />
					</div>
				</div>

				<div class="cms-login-field">
					<label
						for="password"
						class="cms-field-label">
						{_('Passwort')}
					</label>
					<div class="cms-field-control">
						<input
							id="password"
							name="password"
							type="password"
							autocomplete="current-password"
							required
							class="cms-input" />
					</div>
				</div>

				<div class="cms-login-actions">
					<Button
						class="primary"
						style="width: 100%;"
						type="submit"
						icon={IcoLogin}>
						Anmelden
					</Button>
				</div>
			</form>
		</div>
	</div>
</div>

<style lang="postcss">
	.cms-login {
		display: flex;
		min-height: 100%;
		flex-direction: column;
		justify-content: center;
		background-color: var(--cms-color-neutral-50);
		padding: var(--cms-space-12) var(--cms-space-4);
	}

	.cms-login-logo-wrap,
	.cms-login-message,
	.cms-login-card-wrap {
		width: 100%;
		max-width: 28rem;
		margin-left: auto;
		margin-right: auto;
	}

	.cms-login-logo-wrap {
		margin-top: calc(var(--cms-space-32) * -1);
	}

	.cms-login-logo-box {
		margin: 0 auto;
		width: auto;
	}

	.cms-login-logo-fallback {
		height: var(--cms-space-16);
	}

	.cms-login-message {
		margin-top: var(--cms-space-8);
		border: 1px solid var(--cms-color-danger-700);
		border-radius: var(--cms-radius);
		background-color: color-mix(in srgb, var(--cms-color-danger-700) 18%, white);
		padding: var(--cms-space-2) var(--cms-space-4);
		text-align: center;
		color: var(--cms-color-danger-700);
	}

	.cms-login-card-wrap {
		margin-top: var(--cms-space-10);
	}

	.cms-login-card {
		background-color: var(--cms-color-white);
		padding: var(--cms-space-12) var(--cms-space-6);
		border-radius: var(--cms-radius-lg);
		box-shadow: var(--cms-shadow);
	}

	.cms-login-form {
		display: flex;
		flex-direction: column;
		gap: var(--cms-space-6);
	}

	.cms-login-actions {
		display: flex;
		justify-content: flex-end;
	}

	@media (min-width: 1024px) {
		.cms-login {
			padding-left: var(--cms-space-8);
			padding-right: var(--cms-space-8);
		}
	}
</style>
