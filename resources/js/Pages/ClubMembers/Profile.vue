<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PortalField from './PortalField.vue'
import PortalShell from './PortalShell.vue'

const props = defineProps({
    profile: { type: Object, required: true },
    loginId: { type: String, required: true },
    membershipStatus: { type: String, required: true },
    pendingEmail: { type: String, default: null },
    countries: { type: Object, required: true },
})

const page = usePage()
const ui = computed(() => page.props.ui)
const fields = computed(() => page.props.fields)
const links = computed(() => page.props.links)
const profileFields = {
    sona_name: { required: true, autocomplete: 'nickname' },
    first_name: { autocomplete: 'given-name' },
    last_name: { autocomplete: 'family-name' },
    street_address: { autocomplete: 'street-address' },
    postal_code: { autocomplete: 'postal-code' },
    city: { autocomplete: 'address-level2' },
    country: { autocomplete: 'country' },
    phone: { type: 'tel', autocomplete: 'tel' },
    birth_date: { type: 'date', autocomplete: 'bday' },
}

function profileValues(profile) {
    return Object.fromEntries(Object.keys(profileFields).map((field) => [field, profile[field] ?? '']))
}

function formatDate(value) {
    if (!value) return '—'

    return new Intl.DateTimeFormat(page.props.locale, { timeZone: 'UTC' }).format(new Date(value))
}

const profileForm = useForm(profileValues(props.profile))
const emailForm = useForm({ email: '', current_password: '' })
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' })
const logoutForm = useForm({})

function saveProfile() {
    profileForm.patch(links.value.profile, {
        preserveScroll: true,
        onSuccess: (response) => profileForm.defaults(profileValues(response.props.profile)).reset(),
    })
}

function saveEmail() {
    emailForm.patch(links.value.email, {
        errorBag: 'email',
        preserveScroll: true,
        onSuccess: () => emailForm.reset(),
        onFinish: () => emailForm.reset('current_password'),
    })
}

function savePassword() {
    passwordForm.patch(links.value.password, {
        errorBag: 'password',
        preserveScroll: true,
        onFinish: () => passwordForm.reset(),
    })
}
</script>

<template>
    <PortalShell :title="ui.profile" wide>
        <template #header>
            <form @submit.prevent="logoutForm.post(links.logout)">
                <button type="submit" class="portal-link text-sm" :disabled="logoutForm.processing">{{ ui.logout }}</button>
            </form>
        </template>

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
            <div class="space-y-5">
                <section class="portal-card" aria-labelledby="personal-details">
                    <h2 id="personal-details" class="mb-5 text-xl font-semibold">{{ ui.personal_details }}</h2>
                    <form @submit.prevent="saveProfile">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <PortalField
                                v-for="(config, field) in profileFields" :id="field" :key="field" v-model="profileForm[field]"
                                :label="fields[field]" :error="profileForm.errors[field]" :options="field === 'country' ? countries : null"
                                :type="config.type" :autocomplete="config.autocomplete" :required="config.required"
                            />
                        </div>
                        <button class="portal-button mt-5" type="submit" :disabled="profileForm.processing">{{ ui.save_profile }}</button>
                    </form>
                </section>

                <section class="portal-card" aria-labelledby="membership">
                    <h2 id="membership" class="mb-4 text-xl font-semibold">{{ ui.membership }}</h2>
                    <dl class="grid gap-x-5 gap-y-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-600 dark:text-slate-400">{{ fields.status }}</dt><dd class="mt-1 font-medium">{{ membershipStatus }}</dd></div>
                        <div><dt class="text-slate-600 dark:text-slate-400">{{ fields.joined_at }}</dt><dd class="mt-1 font-medium">{{ formatDate(profile.joined_at) }}</dd></div>
                        <div><dt class="text-slate-600 dark:text-slate-400">{{ fields.left_at }}</dt><dd class="mt-1 font-medium">{{ formatDate(profile.left_at) }}</dd></div>
                        <div class="min-w-0"><dt class="text-slate-600 dark:text-slate-400">{{ ui.login_id }}</dt><dd class="mt-1 break-all font-mono">{{ loginId }}</dd></div>
                    </dl>
                </section>
            </div>

            <div class="space-y-5">
                <section class="portal-card" aria-labelledby="email-address">
                    <h2 id="email-address" class="text-xl font-semibold">{{ fields.email }}</h2>
                    <p class="mt-2 break-all text-sm text-slate-600 dark:text-slate-400">{{ profile.email }}</p>
                    <p v-if="pendingEmail" class="mt-3 break-words rounded-lg bg-amber-50 dark:bg-amber-950 p-3 text-sm text-amber-900 dark:text-amber-200">
                        {{ ui.pending_email }} {{ pendingEmail }}
                    </p>
                    <form class="mt-5 space-y-4" @submit.prevent="saveEmail">
                        <PortalField id="new-email" v-model="emailForm.email" :label="ui.new_email" :error="emailForm.errors.email" type="email" autocomplete="email" required />
                        <PortalField id="email-password" v-model="emailForm.current_password" :label="ui.current_password" :error="emailForm.errors.current_password" type="password" autocomplete="current-password" required />
                        <button class="portal-button" type="submit" :disabled="emailForm.processing">{{ ui.send_confirmation }}</button>
                    </form>
                </section>

                <section class="portal-card" aria-labelledby="change-password">
                    <h2 id="change-password" class="text-xl font-semibold">{{ ui.change_password }}</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ ui.password_hint }}</p>
                    <form class="mt-5 space-y-4" @submit.prevent="savePassword">
                        <PortalField id="current-password" v-model="passwordForm.current_password" :label="ui.current_password" :error="passwordForm.errors.current_password" type="password" autocomplete="current-password" required />
                        <PortalField id="new-password" v-model="passwordForm.password" :label="ui.new_password" :error="passwordForm.errors.password" type="password" autocomplete="new-password" required />
                        <PortalField id="confirm-password" v-model="passwordForm.password_confirmation" :label="ui.confirm_password" :error="passwordForm.errors.password_confirmation" type="password" autocomplete="new-password" required />
                        <button class="portal-button" type="submit" :disabled="passwordForm.processing">{{ ui.save_password }}</button>
                    </form>
                </section>
            </div>
        </div>
    </PortalShell>
</template>
