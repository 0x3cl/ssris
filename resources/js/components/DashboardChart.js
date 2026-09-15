import {
    ArcElement, BarController, BarElement, CategoryScale, Chart, Filler, Legend, LineController, LineElement, LinearScale, PieController, PointElement, Tooltip,
} from 'chart.js';
import { defineComponent, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, Filler, Legend, LineController, LineElement, LinearScale, PieController, PointElement, Tooltip);

export default defineComponent({
    name: 'DashboardChart',
    props: { chartData: { type: Object, required: true }, chartType: { type: String, required: true }, options: { type: Object, default: () => ({}) } },
    setup(props) {
        const canvas = ref(null);
        let chart;
        const render = () => {
            chart?.destroy();
            if (!canvas.value) return;
            chart = new Chart(canvas.value, { type: props.chartType, data: props.chartData, options: { responsive: true, maintainAspectRatio: false, ...props.options } });
        };
        onMounted(render);
        onBeforeUnmount(() => chart?.destroy());
        watch(() => [props.chartData, props.chartType, props.options], render, { deep: true });
        return { canvas };
    },
    template: '<div class="relative h-80"><canvas ref="canvas"></canvas></div>',
});
