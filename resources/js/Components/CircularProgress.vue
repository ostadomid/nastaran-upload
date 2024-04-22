<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{ percentage: number }>();
const done = computed(
    () => ((100 - props.percentage) * 4.24).toFixed(0) + "px"
);
const xCord = computed(() => {
    if (props.percentage > 99) {
        return "48px";
    }
    if (props.percentage >= 0 && props.percentage < 10) {
        return "61px";
    } else {
        return "55px";
    }
});
</script>
<template>
    <svg
        width="155"
        height="155"
        viewBox="-19.375 -19.375 193.75 193.75"
        version="1.1"
        xmlns="http://www.w3.org/2000/svg"
        style="transform: rotate(-90deg)"
    >
        <circle
            r="67.5"
            cx="77.5"
            cy="77.5"
            fill="transparent"
            stroke="#e0e0e0"
            stroke-width="20"
            stroke-dasharray="423.90000000000003px"
            stroke-dashoffset="0"
        ></circle>
        <circle
            r="67.5"
            cx="77.5"
            cy="77.5"
            :stroke="done === '0%' ? '#e0e0e0' : '#76e5b1'"
            stroke-width="20"
            stroke-linecap="round"
            :stroke-dashoffset="done"
            fill="transparent"
            stroke-dasharray="423.90000000000003px"
        ></circle>
        <text
            :x="xCord"
            y="91px"
            fill="#6bdba7"
            font-size="25px"
            font-weight="bold"
            style="transform: rotate(90deg) translate(0px, -159px)"
        >
            {{ percentage.toFixed(0) || 0 }}%
        </text>
    </svg>
</template>
