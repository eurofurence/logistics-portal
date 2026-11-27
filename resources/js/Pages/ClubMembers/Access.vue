<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PortalField from './PortalField.vue'
import PortalShell from './PortalShell.vue'

const props = defineProps({
    mode: { type: String, required: true },
})

const page = usePage()
const ui = computed(() => page.props.ui)
const links = computed(() => page.props.links)
const mode = computed(() => ui.value.access[props.mode])
const needsId = computed(() => props.mode === 'login' || props.mode === 'forgot')
const setsPassword = computed(() => props.mode === 'activation' || props.mode === 'reset')
const token = new URLSearchParams(window.location.hash.slice(1)).get('token')
const missingToken = computed(() => !needsId.value && !token)

const form = useForm({
    login_id: '',
    password: '',
    password_confirmation: '',
    token,
})

function submit() {
    const url = props.mode === 'login' ? links.value.authenticate : links.value[props.mode]

    form.post(url, { onFinish: () => form.reset('password', 'password_confirmation') })
}
</script>

<template>
    <PortalShell :title="mode.title">
        <div class="portal-card">
            <p v-if="mode.description" class="mb-5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ mode.description }}</p>
            <p v-if="missingToken || form.errors.token" role="alert" class="mb-5 rounded-lg bg-red-50 dark:bg-red-950 p-3 text-sm text-red-800 dark:text-red-200">
                {{ form.errors.token || ui.invalid_token }}
            </p>
            <form v-if="!missingToken" class="space-y-4" @submit.prevent="submit">
                <PortalField
                    v-if="needsId" id="login_id" v-model="form.login_id" :label="ui.login_id"
                    :error="form.errors.login_id" autocomplete="username" required
                />
                <PortalField
                    v-if="props.mode === 'login' || setsPassword" id="password" v-model="form.password"
                    :label="setsPassword ? ui.new_password : ui.password" :error="form.errors.password"
                    type="password" :autocomplete="setsPassword ? 'new-password' : 'current-password'" required
                />
                <PortalField
                    v-if="setsPassword" id="password_confirmation" v-model="form.password_confirmation"
                    :label="ui.confirm_password" :error="form.errors.password_confirmation"
                    type="password" autocomplete="new-password" required
                />
                <button class="portal-button w-full" type="submit" :disabled="form.processing">{{ mode.submit }}</button>
            </form>
            <div class="mt-5 text-sm">
                <Link v-if="props.mode === 'login'" class="portal-link" :href="links.forgot">{{ ui.forgot_password }}</Link>
                <Link v-else class="portal-link" :href="links.login">{{ ui.back_to_login }}</Link>
            </div>
        </div>
    </PortalShell>
</template>
