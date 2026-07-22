<script setup>
import { computed, onMounted, ref } from 'vue';
import { formatBRL } from '../../Support/money.js';

const props = defineProps({
    bars: { type: Array, default: () => [] },
    currency: { type: Boolean, default: false },
});

const palette = ref({
    chart1: '#3f3f46',
    border: '#e4e4e7',
    muted: '#a1a1aa',
    foreground: '#09090b',
});

onMounted(() => {
    const styles = getComputedStyle(document.documentElement);
    const read = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;

    palette.value = {
        chart1: read('--color-chart-1', read('--color-primary', palette.value.chart1)),
        border: read('--color-border', palette.value.border),
        muted: read('--color-muted', palette.value.muted),
        foreground: read('--color-foreground', palette.value.foreground),
    };
});

const compactFormatter = new Intl.NumberFormat('pt-BR', { notation: 'compact', maximumFractionDigits: 1 });
const compactCurrencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    notation: 'compact',
    maximumFractionDigits: 1,
});

const hasData = computed(() => props.bars.some((bar) => Number(bar.value) > 0));

const categories = computed(() => props.bars.map((bar) => bar.label));

const series = computed(() => [
    {
        name: props.currency ? 'Total' : 'Quantidade',
        data: props.bars.map((bar) => Number(bar.value) || 0),
    },
]);

function formatAxis(value) {
    return props.currency ? compactCurrencyFormatter.format(value) : compactFormatter.format(Math.round(value));
}

function formatValue(value) {
    return props.currency ? formatBRL(value) : String(Math.round(value));
}

const options = computed(() => ({
    chart: {
        type: 'bar',
        height: 280,
        fontFamily: 'inherit',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: { enabled: true, speed: 300, dynamicAnimation: { enabled: false } },
        background: 'transparent',
        parentHeightOffset: 0,
    },
    colors: [palette.value.chart1],
    plotOptions: {
        bar: {
            borderRadius: 3,
            borderRadiusApplication: 'end',
            columnWidth: '58%',
            maxColumnWidth: 40,
        },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: palette.value.border,
        strokeDashArray: 0,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
        padding: { top: 0, right: 8, bottom: 0, left: 8 },
    },
    states: {
        hover: { filter: { type: 'darken', value: 0.9 } },
        active: { filter: { type: 'none' } },
    },
    xaxis: {
        categories: categories.value,
        tickPlacement: 'on',
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            rotate: 0,
            hideOverlappingLabels: true,
            style: { colors: palette.value.muted, fontSize: '10px' },
        },
    },
    yaxis: {
        labels: {
            style: { colors: palette.value.muted, fontSize: '10px' },
            formatter: (value) => formatAxis(value),
        },
    },
    tooltip: {
        enabled: true,
        style: { fontSize: '12px', fontFamily: 'inherit' },
        y: { formatter: (value) => formatValue(value) },
    },
    legend: { show: false },
}));
</script>

<template>
    <div class="w-full">
        <apexchart
            v-if="hasData"
            type="bar"
            height="280"
            :options="options"
            :series="series"
        />
        <div
            v-else
            class="flex h-[280px] items-center justify-center text-sm text-muted"
        >
            Sem dados no período.
        </div>
    </div>
</template>
