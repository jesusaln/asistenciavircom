<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    config: Object,
});

const form = useForm({
    // PayPal
    paypal_sandbox: props.config.paypal_sandbox || false,
    paypal_client_id: props.config.paypal_client_id || '',
    paypal_client_secret: '', // Nunca mostrar el secreto por seguridad

    // MercadoPago
    mercadopago_sandbox: props.config.mercadopago_sandbox || false,
    mercadopago_public_key: props.config.mercadopago_public_key || '',
    mercadopago_access_token: '', // Secreto

    // Stripe
    stripe_sandbox: props.config.stripe_sandbox || false,
    stripe_public_key: props.config.stripe_public_key || '',
    stripe_secret_key: '', // Secreto
    stripe_webhook_secret: '', // Secreto

    // General
    tienda_online_activa: props.config.tienda_online_activa || false,
});

const updatePaymentConfig = () => {
    form.post(route('empresas.store'), { // Asumiendo que empresas.store actualiza (o put)
        errorBag: 'updatePaymentConfig',
        preserveScroll: true,
        onSuccess: () => form.reset('paypal_client_secret', 'mercadopago_access_token', 'stripe_secret_key', 'stripe_webhook_secret'),
    });
};
</script>

<template>
    <FormSection @submitted="updatePaymentConfig">
        <template #title>
            Pasarelas de Pago
        </template>

        <template #description>
            Configura las credenciales para recibir pagos online de pólizas y tienda. 
            <br><br>
            ⚠️ <strong>Importante:</strong> Deja los campos de "Secreto" o "Token" vacíos si no deseas cambiarlos.
        </template>

        <template #form>
            
            <!-- PayPal -->
            <div class="col-span-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                    <span class="text-2xl mr-2">🅿️</span> PayPal
                </h3>
                <div class="mt-1 border-b border-gray-200 dark:border-slate-800"></div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.paypal_sandbox" />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Activar Modo Sandbox (Pruebas)</span>
                </label>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="paypal_client_id" value="Client ID" />
                <TextInput
                    id="paypal_client_id"
                    v-model="form.paypal_client_id"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.paypal_client_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="paypal_client_secret" value="Client Secret" />
                <TextInput
                    id="paypal_client_secret"
                    v-model="form.paypal_client_secret"
                    type="password"
                    placeholder="Deja vacío para mantener el actual"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.paypal_client_secret" class="mt-2" />
            </div>

            <!-- MercadoPago -->
            <div class="col-span-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                    <span class="text-2xl mr-2">🤝</span> MercadoPago
                </h3>
                <div class="mt-1 border-b border-gray-200 dark:border-slate-800"></div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.mercadopago_sandbox" />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Activar Modo Sandbox / Pruebas</span>
                </label>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="mercadopago_public_key" value="Public Key" />
                <TextInput
                    id="mercadopago_public_key"
                    v-model="form.mercadopago_public_key"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.mercadopago_public_key" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="mercadopago_access_token" value="Access Token" />
                <TextInput
                    id="mercadopago_access_token"
                    v-model="form.mercadopago_access_token"
                    type="password"
                    placeholder="Deja vacío para mantener el actual"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.mercadopago_access_token" class="mt-2" />
            </div>

            <!-- Stripe -->
            <div class="col-span-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                    <span class="text-2xl mr-2">💳</span> Stripe
                </h3>
                <div class="mt-1 border-b border-gray-200 dark:border-slate-800"></div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.stripe_sandbox" />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Activar Modo Sandbox / Test Keys</span>
                </label>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="stripe_public_key" value="Public Key (pk_...)" />
                <TextInput
                    id="stripe_public_key"
                    v-model="form.stripe_public_key"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.stripe_public_key" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="stripe_secret_key" value="Secret Key (sk_...)" />
                <TextInput
                    id="stripe_secret_key"
                    v-model="form.stripe_secret_key"
                    type="password"
                    placeholder="Deja vacío para mantener el actual"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.stripe_secret_key" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="stripe_webhook_secret" value="Webhook Secret (whsec_...)" />
                <TextInput
                    id="stripe_webhook_secret"
                    v-model="form.stripe_webhook_secret"
                    type="password"
                    placeholder="Opcional: Deja vacío para mantener el actual"
                    class="mt-1 block w-full"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Necesario para validar webhooks de Stripe en producción.</p>
                <InputError :message="form.errors.stripe_webhook_secret" class="mt-2" />
            </div>

        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="mr-3">
                Guardado.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Guardar Configuración
            </PrimaryButton>
        </template>
    </FormSection>
</template>
