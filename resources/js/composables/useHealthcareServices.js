import { useHealthcare } from './useHealthcare';
import { createHealthcareClient } from '@/services/healthcareClient';
import { patientService } from '@/services/patient.service';
import { appointmentService } from '@/services/appointment.service';
import { pharmacyService } from '@/services/pharmacy.service';
import { planService } from '@/services/plan.service';
import { maxService } from '@/services/max.service';
import { triageService } from '@/services/triage.service';
export function useHealthcareServices() {
    const { context } = useHealthcare();
    const client = createHealthcareClient(context);
    return {
        patient: patientService(client),
        appointment: appointmentService(client),
        pharmacy: pharmacyService(client),
        plan: planService(client),
        max: maxService(client),
        triage: triageService(client),
    };
}
