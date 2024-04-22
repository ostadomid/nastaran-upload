<script setup lang="ts">
import { ref } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import ArrowIcon from "@/Components/ArrowIcon.vue";
import { createUpload } from "@mux/upchunk";
import CircularProgress from "@/Components/CircularProgress.vue";
import { computed } from "vue";

const file = ref<null | HTMLInputElement>(null);
const video = ref<null | HTMLInputElement>(null);
const form = useForm({ videoFile: null });
const isUploading = ref(false);
const percentage = ref(0);
const flash = computed(() => usePage().props["flash"]);

function upload() {
    if (file.value && file.value.files?.[0]) {
        const fileToUpload = file.value.files?.[0];
        console.log(fileToUpload);
    } else {
        alert("No file to upload!");
    }
}

function fileChanged(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0] && video.value) {
        form.videoFile = target.files[0];
        video.value.src = URL.createObjectURL(target.files[0]);
        video.value.classList.add("shadow-lg");
        video.value.classList.remove("h-0");
    } else {
        form.videoFile = null;
        video.value!.classList.remove("shadow-lg");
        video.value!.classList.add("h-0");
        video.value!.src = "";
    }
}

function handleSubmit() {
    if (!form.videoFile) {
        return alert("No file selecte!");
    }

    const uploader = createUpload({
        endpoint: "/upload",
        file: form.videoFile,
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": usePage().props["csrf"] as string,
        },
        chunkSize: 10 * 1024,
    });
    uploader.on("attempt", () => {
        // error = null
        // uploading = true
    });

    uploader.on("progress", (p) => {
        console.log(p);
        percentage.value = p.detail;
        isUploading.value = true;
    });

    uploader.on("success", (e) => {
        console.log("Done!");
        console.log(usePage().props);
        isUploading.value = false;
        percentage.value = 0;
        router.reload();
    });

    uploader.on("error", (error) => {
        console.error(error.detail.message);
    });
}
</script>

<template>
    <div
        class="min-h-dvh bg-gradient-to-b from-green-300 to-green-700 flex flex-col justify-center items-center"
    >
        <Head>
            <title>Upload</title>
        </Head>
        <p
            v-if="flash"
            class="text-center my-4 shadow-md text-white bg-white/10 border border-green-800/50 p-4 rounded-md"
        >
            💾 {{ flash }}
        </p>
        <video
            ref="video"
            class="max-h-[250px] h-0 object-cover rounded-md mb-4 shadow-green-800"
        />
        <form
            class="flex flex-col justify-center items-center gap-4"
            @submit.prevent="handleSubmit"
        >
            <div
                v-if="!isUploading"
                class="cursor-pointer border-2 rounded-full border-white/95 size-20 p-6"
                @click="
                    () => {
                        file?.click();
                    }
                "
            >
                <ArrowIcon v-bind="{ h: 32, w: 32 }" class="text-white">
                    <input
                        type="file"
                        ref="file"
                        @change="fileChanged"
                        style="visibility: hidden; width: 0px; height: 0px"
                    />
                </ArrowIcon>
            </div>
            <p class="text-red-500 font-bold" v-if="form.errors.videoFile">
                {{ form.errors.videoFile }}
            </p>
            <CircularProgress v-if="isUploading" :percentage="percentage" />
            <button
                :disabled="isUploading"
                type="submit"
                class="disabled:bg-slate-300 disabled:text-slate-500 shadow-md rounded-md border border-green-200 px-4 py-2 bg-white/25 hover:bg-green-500/70"
            >
                {{ isUploading ? "⚡ Uploading..." : "🏁 Start" }}
            </button>
        </form>
    </div>
</template>
