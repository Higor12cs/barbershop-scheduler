<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import { route } from "ziggy-js";
import AppLayout from "../../Layouts/AppLayout.vue";
import ScheduleToolbar from "../../Components/Schedule/ScheduleToolbar.vue";
import ScheduleGrid from "../../Components/Schedule/ScheduleGrid.vue";
import AppointmentFormModal from "../../Components/Schedule/AppointmentFormModal.vue";
import AppointmentDetailsModal from "../../Components/Schedule/AppointmentDetailsModal.vue";
import ConfirmDialog from "../../Components/ConfirmDialog.vue";
import EmptyState from "../../Components/EmptyState.vue";
import {
  addDays,
  minutesToTime,
  nowMinutes,
  toISODate,
  todayISO,
  weekDays,
} from "../../Support/date.js";

const props = defineProps({
  view: { type: String, required: true },
  date: { type: String, required: true },
  today: { type: String, required: true },
  rangeStart: { type: String, required: true },
  rangeEnd: { type: String, required: true },
  selectedEmployeeId: { type: [Number, null], default: null },
  settings: { type: Object, required: true },
  employees: { type: Array, default: () => [] },
  services: { type: Array, default: () => [] },
  customers: { type: Array, default: () => [] },
  appointments: { type: Array, default: () => [] },
  availability: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  whatsappReady: { type: Boolean, default: false },
  bookingNotifyEnabled: { type: Boolean, default: false },
  messageTypes: { type: Array, default: () => [] },
});

const page = usePage();

const detailsAppointment = ref(null);
const confirmComplete = ref(null);
const confirmDelete = ref(null);
const confirmMove = ref(null);
const confirmForce = ref(null);
const confirmSaleRemoval = ref(null);
const notifyCreate = ref(null);
const processing = ref(false);

watch(
  () => props.appointments,
  (list) => {
    if (detailsAppointment.value) {
      detailsAppointment.value =
        list.find((item) => item.id === detailsAppointment.value.id) ?? null;
    }
  },
);

watch(
  () => page.props.flash?.created_appointment,
  (created) => {
    if (
      created &&
      props.bookingNotifyEnabled &&
      props.whatsappReady &&
      created.has_phone
    ) {
      notifyCreate.value = created;
    }
  },
);

const formState = reactive({
  show: false,
  mode: "create",
  initial: null,
  appointmentId: null,
});

const weekEmployeeId = computed(
  () => props.selectedEmployeeId ?? props.employees[0]?.id ?? null,
);
const weekEmployee = computed(
  () =>
    props.employees.find((employee) => employee.id === weekEmployeeId.value) ||
    null,
);

const columns = computed(() => {
  if (props.view === "week") {
    return weekDays(props.rangeStart).map((day) => ({
      key: day.date,
      label: day.weekday,
      sublabel: day.dayMonth,
    }));
  }

  const employees = props.selectedEmployeeId
    ? props.employees.filter(
        (employee) => employee.id === props.selectedEmployeeId,
      )
    : props.employees;

  return employees.map((employee) => ({
    key: String(employee.id),
    label: employee.name,
    color: employee.color,
  }));
});

const gridAppointments = computed(() => {
  if (props.view === "week") {
    return props.appointments
      .filter((appointment) => appointment.employee_id === weekEmployeeId.value)
      .map((appointment) => ({ ...appointment, columnKey: appointment.date }));
  }

  return props.appointments
    .filter(
      (appointment) =>
        !props.selectedEmployeeId ||
        appointment.employee_id === props.selectedEmployeeId,
    )
    .map((appointment) => ({
      ...appointment,
      columnKey: String(appointment.employee_id),
    }));
});

const unavailableByColumn = computed(() => {
  const byColumn = {};

  props.availability.forEach((entry) => {
    const key =
      props.view === "week"
        ? entry.employee_id === weekEmployeeId.value
          ? entry.date
          : null
        : entry.date === props.date
          ? String(entry.employee_id)
          : null;

    if (key !== null) {
      byColumn[key] = [...(byColumn[key] ?? []), ...entry.ranges];
    }
  });

  return byColumn;
});

const nowLine = computed(() => {
  if (props.view === "week") {
    const inRange =
      props.today >= props.rangeStart && props.today <= props.rangeEnd;

    return inRange
      ? { minutes: nowMinutes(), columnKey: props.today }
      : { minutes: null, columnKey: null };
  }

  return props.date === props.today
    ? { minutes: nowMinutes(), columnKey: null }
    : { minutes: null, columnKey: null };
});

const hasColumns = computed(() => columns.value.length > 0);

function reload(overrides = {}) {
  const params = {
    view: props.view,
    date: props.date,
    ...(props.selectedEmployeeId
      ? { employee_id: props.selectedEmployeeId }
      : {}),
    ...overrides,
  };

  router.get(route("appointments.index"), params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function shiftDate(amount) {
  reload({ date: toISODate(addDays(props.date, amount)) });
}

function onPrev() {
  shiftDate(props.view === "week" ? -7 : -1);
}

function onNext() {
  shiftDate(props.view === "week" ? 7 : 1);
}

function onToday() {
  reload({ date: todayISO() });
}

function onChangeView(view) {
  const overrides = { view };

  if (view === "week" && !props.selectedEmployeeId && props.employees[0]) {
    overrides.employee_id = props.employees[0].id;
  }

  reload(overrides);
}

function onChangeDate(value) {
  if (value) {
    reload({ date: value });
  }
}

function onSelectEmployee(id) {
  reload({ employee_id: id ?? undefined });
}

const lineMinutes = computed(() => props.settings.line_minutes ?? 30);

function defaultStart() {
  const dayStart = props.settings.start_hour * 60;

  if (props.date !== props.today) {
    return minutesToTime(dayStart);
  }

  // Rounds up so a new appointment never defaults to a time already past.
  const rounded =
    Math.ceil(nowMinutes() / lineMinutes.value) * lineMinutes.value;

  return minutesToTime(
    Math.min(Math.max(rounded, dayStart), (props.settings.end_hour - 1) * 60),
  );
}

function buildCreateInitial({ employeeId, date, startTime }) {
  const service = props.services[0] || null;

  return {
    customer_id: null,
    employee_id: employeeId ?? props.employees[0]?.id ?? null,
    product_id: service?.id ?? null,
    date: date ?? props.date,
    start_time: startTime ?? defaultStart(),
    duration_minutes: service?.duration_minutes ?? "",
    price: service?.price ?? "",
    notes: "",
  };
}

function openCreate() {
  formState.mode = "create";
  formState.appointmentId = null;
  formState.initial = buildCreateInitial({
    employeeId: props.selectedEmployeeId ?? weekEmployeeId.value,
    date: props.date,
    startTime: defaultStart(),
  });
  formState.show = true;
}

function onSlotClick({ columnKey, startMinutes }) {
  const startTime = minutesToTime(startMinutes);

  if (props.view === "week") {
    formState.initial = buildCreateInitial({
      employeeId: weekEmployeeId.value,
      date: columnKey,
      startTime,
    });
  } else {
    formState.initial = buildCreateInitial({
      employeeId: Number(columnKey),
      date: props.date,
      startTime,
    });
  }

  formState.mode = "create";
  formState.appointmentId = null;
  formState.show = true;
}

const confirmMoveMessage = computed(() => {
  if (!confirmMove.value) {
    return "";
  }

  const customer =
    confirmMove.value.appointment.customer_name ?? "este agendamento";
  const target = confirmMove.value.targetEmployee?.name ?? "outro profissional";

  return `Mover o agendamento de ${customer} para ${target}?`;
});

function commitReschedule(
  { id, employeeId, date, startMinutes },
  force = false,
) {
  const startTime = minutesToTime(startMinutes);
  const appointment = props.appointments.find((item) => item.id === id);
  const employee =
    props.employees.find((item) => item.id === employeeId) || null;
  const endMinutes = startMinutes + (appointment?.duration_minutes ?? 0);

  router.patch(
    route("appointments.reschedule", id),
    { employee_id: employeeId, date, start_time: startTime, force },
    {
      preserveState: true,
      preserveScroll: true,
      onError: (errors) => {
        if (errors.availability) {
          confirmForce.value = {
            move: { id, employeeId, date, startMinutes },
            reason: errors.availability,
          };
        }
      },
      optimistic: (current) => ({
        appointments: current.appointments.map((item) =>
          item.id === id
            ? {
                ...item,
                employee_id: employeeId,
                employee_name: employee?.name ?? item.employee_name,
                employee_color: employee?.color ?? item.employee_color,
                date,
                start_time: startTime,
                end_time: minutesToTime(endMinutes),
                start_minutes: startMinutes,
              }
            : item,
        ),
      }),
    },
  );
}

function onReschedule({ id, columnKey, startMinutes }) {
  const appointment = props.appointments.find((item) => item.id === id);

  if (!appointment) {
    return;
  }

  const employeeId =
    props.view === "week" ? weekEmployeeId.value : Number(columnKey);
  const date = props.view === "week" ? columnKey : props.date;

  if (props.view !== "week" && employeeId !== appointment.employee_id) {
    confirmMove.value = {
      id,
      employeeId,
      date,
      startMinutes,
      appointment,
      targetEmployee:
        props.employees.find((item) => item.id === employeeId) || null,
    };

    return;
  }

  commitReschedule({ id, employeeId, date, startMinutes });
}

function doMove() {
  if (!confirmMove.value) {
    return;
  }

  const { id, employeeId, date, startMinutes } = confirmMove.value;

  commitReschedule({ id, employeeId, date, startMinutes });
  confirmMove.value = null;
}

function doForceReschedule() {
  if (!confirmForce.value) {
    return;
  }

  const { move } = confirmForce.value;

  confirmForce.value = null;
  commitReschedule(move, true);
}

function onAppointmentClick(appointment) {
  detailsAppointment.value = appointment;
}

function closeDetails() {
  detailsAppointment.value = null;
}

function patchStatus(id, status, options = {}) {
  router.patch(
    route("appointments.status", id),
    { status },
    {
      preserveScroll: true,
      preserveState: true,
      ...options,
    },
  );
}

function changeStatus(status) {
  const appointment = detailsAppointment.value;

  if (!appointment) {
    return;
  }

  if (appointment.has_sale && status !== "completed") {
    confirmSaleRemoval.value = { id: appointment.id, status };

    return;
  }

  patchStatus(appointment.id, status, { onSuccess: closeDetails });
}

function doSaleRemoval() {
  if (!confirmSaleRemoval.value) {
    return;
  }

  const { id, status } = confirmSaleRemoval.value;

  patchStatus(id, status, {
    onStart: () => (processing.value = true),
    onFinish: () => {
      processing.value = false;
      confirmSaleRemoval.value = null;
    },
  });
}

function sendNotification(id, type) {
  router.post(
    route("appointments.notify", id),
    { type },
    { preserveScroll: true, preserveState: true },
  );
}

function onSendMessage(type) {
  if (detailsAppointment.value) {
    sendNotification(detailsAppointment.value.id, type);
  }
}

function doNotifyCreate() {
  if (notifyCreate.value) {
    sendNotification(notifyCreate.value.id, "booking");
  }

  notifyCreate.value = null;
}

function requestComplete() {
  confirmComplete.value = detailsAppointment.value;
}

function doComplete() {
  if (!confirmComplete.value) {
    return;
  }

  router.patch(
    route("appointments.status", confirmComplete.value.id),
    { status: "completed" },
    {
      preserveScroll: true,
      preserveState: true,
      onStart: () => (processing.value = true),
      onFinish: () => {
        processing.value = false;
        confirmComplete.value = null;
      },
    },
  );
}

function openEdit() {
  const appointment = detailsAppointment.value;

  if (!appointment) {
    return;
  }

  formState.mode = "edit";
  formState.appointmentId = appointment.id;
  formState.initial = {
    customer_id: appointment.customer_id,
    employee_id: appointment.employee_id,
    product_id: appointment.product_id,
    date: appointment.date,
    start_time: appointment.start_time,
    duration_minutes: appointment.duration_minutes,
    price: appointment.price,
    notes: appointment.notes ?? "",
  };
  formState.show = true;
  closeDetails();
}

function requestDelete() {
  confirmDelete.value = detailsAppointment.value;
}

function doDelete() {
  if (!confirmDelete.value) {
    return;
  }

  router.delete(route("appointments.destroy", confirmDelete.value.id), {
    preserveScroll: true,
    preserveState: true,
    onStart: () => (processing.value = true),
    onFinish: () => {
      processing.value = false;
      confirmDelete.value = null;
    },
  });
}
</script>

<template>
  <AppLayout full-height>
    <Head title="Agenda" />

    <div class="flex min-h-0 flex-1 flex-col gap-4">
      <ScheduleToolbar
        class="shrink-0"
        :date="date"
        :view="view"
        :selected-employee-id="selectedEmployeeId"
        :employees="employees"
        @prev="onPrev"
        @next="onNext"
        @today="onToday"
        @change-view="onChangeView"
        @change-date="onChangeDate"
        @select-employee="onSelectEmployee"
        @create="openCreate"
      />

      <div
        v-if="view === 'week' && weekEmployee"
        class="flex shrink-0 items-center gap-2 text-sm text-secondary"
      >
        <span
          class="size-2.5 rounded-full"
          :style="{ backgroundColor: weekEmployee.color }"
        />
        Exibindo a semana de
        <span class="font-medium text-foreground">{{ weekEmployee.name }}</span>
      </div>

      <ScheduleGrid
        v-if="hasColumns"
        class="min-h-0 flex-1"
        :columns="columns"
        :appointments="gridAppointments"
        :settings="settings"
        :now-line="nowLine"
        :unavailable="unavailableByColumn"
        @slot-click="onSlotClick"
        @appointment-click="onAppointmentClick"
        @reschedule="onReschedule"
      />

      <EmptyState
        v-else
        icon="calendar"
        title="Nenhum Funcionário Ativo"
        description="Cadastre um funcionário para começar a montar a agenda."
      />
    </div>

    <AppointmentFormModal
      :show="formState.show"
      :mode="formState.mode"
      :initial="formState.initial"
      :appointment-id="formState.appointmentId"
      :customers="customers"
      :services="services"
      :employees="employees"
      @close="formState.show = false"
    />

    <AppointmentDetailsModal
      :show="detailsAppointment !== null"
      :appointment="detailsAppointment"
      :whatsapp-ready="whatsappReady"
      :message-types="messageTypes"
      @close="closeDetails"
      @status="changeStatus"
      @complete="requestComplete"
      @edit="openEdit"
      @delete="requestDelete"
      @send="onSendMessage"
    />

    <ConfirmDialog
      :show="confirmComplete !== null"
      title="Finalizar Atendimento"
      message="Finalizar este atendimento? Uma venda será gerada."
      confirm-label="Finalizar"
      variant="primary"
      :processing="processing"
      @confirm="doComplete"
      @cancel="confirmComplete = null"
    />

    <ConfirmDialog
      :show="confirmDelete !== null"
      title="Excluir Agendamento"
      message="Tem certeza que deseja excluir este agendamento?"
      confirm-label="Excluir"
      :processing="processing"
      @confirm="doDelete"
      @cancel="confirmDelete = null"
    />

    <ConfirmDialog
      :show="confirmMove !== null"
      title="Trocar de Profissional"
      :message="confirmMoveMessage"
      confirm-label="Mover"
      variant="primary"
      @confirm="doMove"
      @cancel="confirmMove = null"
    />

    <ConfirmDialog
      :show="confirmForce !== null"
      title="Horário Indisponível"
      :message="`${confirmForce?.reason ?? ''} Deseja mover mesmo assim?`"
      confirm-label="Mover Mesmo Assim"
      variant="primary"
      @confirm="doForceReschedule"
      @cancel="confirmForce = null"
    />

    <ConfirmDialog
      :show="confirmSaleRemoval !== null"
      title="Reabrir Agendamento"
      message="Este atendimento já gerou uma venda. Ao reabri-lo, a venda será excluída. Deseja continuar?"
      confirm-label="Reabrir e Excluir Venda"
      :processing="processing"
      @confirm="doSaleRemoval"
      @cancel="confirmSaleRemoval = null"
    />

    <ConfirmDialog
      :show="notifyCreate !== null"
      title="Enviar Notificação"
      :message="`Enviar a notificação de agendamento para ${notifyCreate?.customer_name ?? 'o cliente'} via WhatsApp?`"
      confirm-label="Enviar"
      variant="primary"
      @confirm="doNotifyCreate"
      @cancel="notifyCreate = null"
    />
  </AppLayout>
</template>
