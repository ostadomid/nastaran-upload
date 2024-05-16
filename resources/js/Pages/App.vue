<script setup lang="ts">
import { Semaphore, Mutex } from "async-mutex";
import { ref, type Ref } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import ArrowIcon from "@/Components/ArrowIcon.vue";
import ArchiveIcon from "@/Components/ArchiveIcon.vue";
import VideoIcon from "@/Components/VideoIcon.vue";
import FileIcon from "@/Components/FileIcon.vue";
import { createUpload } from "@mux/upchunk";
import CircularProgress from "@/Components/CircularProgress.vue";
import { computed } from "vue";
import { nextTick } from "vue";
import { watch } from "vue";
const MAX_CUNCURRENT = 1;
const CHUNK_SIZE = 1 * 1024;
const MAX_ATTEMPTS = 15;

const file = ref<null | HTMLInputElement>(null);
const ul = ref<null | HTMLUListElement>(null);
const form = useForm<{ filesToUpload: FileList }>({ filesToUpload: null });
const selectedFiles = computed(() => Array.from(form.filesToUpload ?? []));
const toBeUploadedCount = ref(0);
const isUploading = computed(() => toBeUploadedCount.value > 0);
const percentage = ref([0]);
// const flash = computed(() => usePage().props["flash"]);
const showDetails = ref(false);
const details: Ref<Array<String>> = ref([""]);
const showSuccess = ref(false);

watch(toBeUploadedCount, (newVal, oldVal) => {
    if (newVal === 0 && oldVal === 1) {
        showSuccess.value = true;
    }
});

const sempaphore = new Semaphore(MAX_CUNCURRENT); //computed(() => new Semaphore(form.filesToUpload.length));
const mutex = new Mutex();

function humanFriendly(value) {
    const format = Intl.NumberFormat("IR", { maximumFractionDigits: 2 }).format;
    return value < 1024 * 1024
        ? format(value / 1024) + " KB"
        : format(value / 1024 / 1024) + " MB";
}

function chooseComponent(file: File) {
    if (file.name.toLowerCase().endsWith(".rar")) {
        return ArchiveIcon;
    } else if (file.type === "video/mp4") {
        return VideoIcon;
    } else if (file.type === "application/x-zip-compressed") {
        return ArchiveIcon;
    } else {
        return FileIcon;
    }
}

function fileChanged(e: Event) {
    const target = e.target as HTMLInputElement;
    form.filesToUpload = target.files;
    percentage.value = Array(form.filesToUpload.length).fill(0);
    showSuccess.value = false; // Remove the last Success Message
    details.value = []; // Clear Logs
}

function handleSubmit() {
    showSuccess.value = false; // Remove the last Success Message
    details.value = []; // Clear Logs

    if (!form.filesToUpload) {
        return alert("No file selecte!");
    }
    toBeUploadedCount.value = form.filesToUpload.length;
    details.value = [];
    showDetails.value = true;

    for (let idx = 0; idx < form.filesToUpload.length; idx++) {
        sempaphore.acquire().then(([value, release]) => {
            // currentFileIndex.value = idx;
            const uploader = createUpload({
                endpoint: "/upload",
                file: form.filesToUpload.item(idx),
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": usePage().props["csrf"] as string,
                    "X-FILE-NAME": form.filesToUpload.item(idx).name,
                },
                chunkSize: CHUNK_SIZE,
                attempts: MAX_ATTEMPTS,
            });
            // uploaderInstance.value = uploader;
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
                percentage.value[idx] = p.detail;
                // isUploading.value = true;
            });

            uploader.on("success", (e) => {
                release(); // Release The Semaphore
                mutex.acquire().then((releaseMutex) => {
                    toBeUploadedCount.value = toBeUploadedCount.value - 1;
                    releaseMutex();
                });
                // router.reload();
            });

            uploader.on("error", ({ detail }) => {
                details.value.push(
                    `❌ Chunk Number ${detail.chunkNumber} Failed [Attempts=${detail.attempts}]!!!`
                );
                nextTick(() => {
                    ul.value.scrollTo(0, ul.value.scrollHeight);
                });
                release(); // Release The Semaphore
                mutex.acquire().then((releaseMutex) => {
                    toBeUploadedCount.value = toBeUploadedCount.value - 1;
                    releaseMutex();
                });
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
        }); // end-then
    } // end-for
}
</script>

<template>
    <div
        class="min-h-dvh bg-gradient-to-b from-green-300 to-green-700 flex flex-col justify-center items-center"
    >
        <Head>
            <title>Upload</title>
        </Head>
        <h2
            v-if="showSuccess"
            class="select-none border border-green-600 p-4 rounded-lg my-4 bg-gradient-to-b from-white/50 to-gray-300/50"
        >
            👻Upload Finished!
        </h2>
        <ul
            v-if="selectedFiles.length > 0"
            class="w-full sm:max-w-sm md:max-w-md lg:max-w-lg my-4 divide-y divide-gray-100 border border-gray-100 rounded-md px-4"
        >
            <li
                class="flex justify-between gap-x-6 py-5 w-full"
                v-for="(fileItem, idx) in selectedFiles"
                :key="idx"
            >
                <div
                    class="flex min-w-0 gap-x-4 w-full items-center justify-between"
                >
                    <component
                        :is="chooseComponent(fileItem)"
                        :size="48"
                        class="text-white/80"
                    />
                    <div class="min-w-0 flex-auto overflow-hidden">
                        <p
                            class="truncate text-base font-semibold leading-6 text-gray-800"
                        >
                            {{ fileItem.name }}
                        </p>
                        <p
                            class="mt-1 truncate text-sm font-semibold leading-5 text-[#ffee08]"
                        >
                            {{ humanFriendly(fileItem.size) }}
                        </p>
                    </div>
                    <div class="shrink-0">
                        <CircularProgress
                            :size="96"
                            text-color="black"
                            :percentage="percentage[idx]"
                        />
                    </div>
                </div>
            </li>
        </ul>
        <form
            class="flex flex-col justify-center items-center gap-4 w-full sm:max-w-sm md:max-w-md lg:max-w-lg"
            v-on:submit.prevent="handleSubmit"
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
                        :multiple="true"
                        @change="fileChanged"
                        style="visibility: hidden; width: 0px; height: 0px"
                    />
                </ArrowIcon>
            </div>
            <p class="text-red-500 font-bold" v-if="form.errors.filesToUpload">
                {{ form.errors.filesToUpload }}
            </p>

            <ul
                ref="ul"
                v-show="showDetails"
                class="bg-white/50 rounded-md w-full sm:max-w-sm md:max-w-md lg:max-w-lg overflow-y-auto h-[200px] min-w-[20ch]"
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
        </form>
    </div>
</template>
