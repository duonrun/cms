<script lang="ts">
	import type { User } from '$types/data';
	import { _ } from '$lib/locale';
	import { saveProfile } from '$lib/user';
	import Document from '$shell/Document.svelte';
	import Input from '$shell/controls/Input.svelte';
	import Password from '$shell/controls/Password.svelte';
	import NavToggle from '$shell/NavToggle.svelte';
	import Button from '$shell/Button.svelte';
	import Headline from '$shell/Headline.svelte';
	import IcoSave from '$shell/icons/IcoSave.svelte';
	import Pane from '$shell/Pane.svelte';

	type Props = {
		data: { user: User };
	};

	let { data = $bindable() }: Props = $props();

	async function save() {
		saveProfile(data.user);
	}
</script>

<div class="cms-userprofile">
	<div class="headerbar">
		<NavToggle />
		<div class="controls cms-userprofile-controls">
			<Button
				class="primary"
				icon={IcoSave}
				onclick={save}>
				Speichern
			</Button>
		</div>
	</div>
	<Document>
		<div class="cms-userprofile-headline">
			<Headline>{_('Benutzerprofil')}</Headline>
		</div>

		<Pane>
			<Input
				id="email"
				bind:value={data.user.email}
				label={_('E-Mail-Adresse')}
				required />
			<Input
				id="username"
				bind:value={data.user.username}
				label={_('Benutzername')} />
			<Input
				id="name"
				bind:value={data.user.name}
				label={_('Vollständiger Name')} />
			<Password
				id="password"
				bind:value={data.user.password}
				label={_('Neues Passwort')} />
			<Password
				id="passwordRepeat"
				bind:value={data.user.passwordRepeat}
				label={_('Neues Passwort wiederholen')} />
		</Pane>
	</Document>
</div>

<style lang="postcss">
	.cms-userprofile {
		display: flex;
		min-height: 100vh;
		flex-direction: column;
	}

	.cms-userprofile-controls {
		display: flex;
		flex-direction: row;
		justify-content: flex-end;
		gap: var(--cms-space-4);
		padding: var(--cms-space-6) var(--cms-space-4);
	}

	.cms-userprofile-headline {
		margin-top: var(--cms-space-8);
	}
</style>
