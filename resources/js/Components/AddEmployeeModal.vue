<script setup>
import { ref, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    show: Boolean,
    outlet: Object,
});

const emit = defineEmits(["close"]);

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("outlets.employees.store", props.outlet.id), {
        onSuccess: () => {
            form.reset();
            emit("close");
        },
        preserveScroll: true,
    });
};

const close = () => {
    form.reset();
    emit("close");
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div
            class="rounded-2xl border border-border/80 bg-surface-1 p-6 text-text shadow-xl"
        >
            <!-- Header -->
            <div
                class="mb-6 flex items-center justify-between border-b border-border/60 pb-4"
            >
                <h2
                    class="text-xl font-bold text-text"
                    style="font-family: 'Oswald', sans-serif"
                >
                    Tambah Karyawan Baru
                </h2>
                <button
                    @click="close"
                    class="text-text-2 transition duration-fast hover:text-text"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Success Message -->
            <div
                v-if="successMessage"
                class="mb-4 rounded-lg border border-success/30 bg-success/10 p-3 text-success"
            >
                {{ successMessage }}
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Name Field -->
                <div>
                    <InputLabel
                        for="name"
                        value="Nama Lengkap"
                    />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1"
                        v-model="form.name"
                        required
                        autofocus
                        placeholder="Masukkan nama karyawan"
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Email Field -->
                <div>
                    <InputLabel
                        for="email"
                        value="Email"
                    />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1"
                        v-model="form.email"
                        required
                        placeholder="karyawan@contoh.com"
                    />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <!-- Password Field -->
                <div>
                    <InputLabel
                        for="password"
                        value="Password Sementara"
                    />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1"
                        v-model="form.password"
                        required
                        placeholder="Password sementara untuk karyawan"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                    <p class="mt-1 text-sm text-text-2">
                        Karyawan akan mengubah password ini setelah login
                        pertama kali
                    </p>
                </div>

                <!-- Password Confirmation Field -->
                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Konfirmasi Password"
                    />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1"
                        v-model="form.password_confirmation"
                        required
                        placeholder="Konfirmasi password"
                    />
                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-2"
                    />
                </div>

                <!-- Actions -->
                <div
                    class="flex items-center justify-end space-x-3 border-t border-border/60 pt-6"
                >
                    <SecondaryButton
                        @click="close"
                        :disabled="form.processing"
                    >
                        Batal
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-60': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>Tambah Karyawan</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap");

h2 {
    font-family: "Oswald", sans-serif;
}

body {
    font-family: "Roboto", sans-serif;
}
</style>
