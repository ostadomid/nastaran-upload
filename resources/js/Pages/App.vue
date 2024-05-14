<script setup lang="ts">
import { ref, type Ref } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import ArrowIcon from "@/Components/ArrowIcon.vue";
import { createUpload } from "@mux/upchunk";
import CircularProgress from "@/Components/CircularProgress.vue";
import { computed } from "vue";
import { nextTick } from "vue";

const file = ref<null | HTMLInputElement>(null);
const video = ref<null | HTMLInputElement>(null);
const ul = ref<null | HTMLUListElement>(null);
const form = useForm({ videoFile: null });
const isUploading = ref(false);
const percentage = ref(0);
const flash = computed(() => usePage().props["flash"]);
const showDetails = ref(false);
const details: Ref<Array<String>> = ref([""]);
const uploaderInstance = ref(null);

function tryAgain() {
    uploaderInstance.value?.resume();
}

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
    details.value = [];
    showDetails.value = true;

    const uploader = createUpload({
        endpoint: "/upload",
        file: form.videoFile,
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": usePage().props["csrf"] as string,
        },
        chunkSize: 1 * 1024,
        attempts: 15,
    });
    uploaderInstance.value = uploader;
    uploader.on("attempt", ({ detail }) => {
        details.value.push(`🤲 Uploading [${detail.chunkNumber}]`);
        ul.value.scrollTo(0, ul.value.scrollHeight);
        // error = null
        // uploading = true
    });
    uploader.on("chunkSuccess", ({ detail }) => {
        details.value.push(`✅ Uploaded[${detail.chunk}]`);
        nextTick(() => {
            ul.value.scrollTo(0, ul.value.scrollHeight);
        });
    });
    uploader.on("attemptFailure", ({ detail }) => {
        details.value.push(
            `⚠ Trying Again[${detail.chunkNumber}][Left:${detail.attemptsLeft}]`
        );
        nextTick(() => {
            ul.value.scrollTo(0, ul.value.scrollHeight);
        });
    });

    uploader.on("progress", (p) => {
        console.log(p);
        percentage.value = p.detail;
        isUploading.value = true;
    });

    uploader.on("success", (e) => {
        details.value = [];
        showDetails.value = false;
        console.log("Done!");
        console.log(usePage().props);
        isUploading.value = false;
        percentage.value = 0;
        router.reload();
    });

    uploader.on("error", ({ detail }) => {
        details.value.push(
            `❌ Chunk Number ${detail.chunkNumber} Failed [Attempts=${detail.attempts}]!!!`
        );
        nextTick(() => {
            ul.value.scrollTo(0, ul.value.scrollHeight);
        });
        console.error(detail.message);
    });
    uploader.on("offline", () => {
        details.value.push("🚨 Internet Offline");
        nextTick(() => {
            ul.value.scrollTo(0, ul.value.scrollHeight);
        });
    });
    uploader.on("online", () => {
        details.value.push("🌍 Internet Online");
        nextTick(() => {
            ul.value.scrollTo(0, ul.value.scrollHeight);
        });
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
            <ul
                ref="ul"
                v-show="showDetails"
                class="bg-white/50 rounded-md max-h-56 overflow-y-auto"
            >
                <li v-for="(line, idx) in details" :key="idx">
                    {{ line }}
                </li>
            </ul>
            <button
                :disabled="isUploading"
                type="submit"
                class="disabled:bg-slate-300 disabled:text-slate-500 shadow-md rounded-md border border-green-200 px-4 py-2 bg-white/25 hover:bg-green-500/70"
            >
                {{ isUploading ? "⚡ Uploading..." : "🏁 Start" }}
            </button>
            <button @click="tryAgain">Try Again</button>
        </form>
    </div>
</template>
