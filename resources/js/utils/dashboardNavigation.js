import { managerMenu, patientMenu } from '@/constants/healthcareNavigation';

export function navItemsForRole(role) {
    if (role === 'manager') {
        return managerMenu;
    }

    // "Início" points at the fixture-driven demo page; the real landing page is /dashboard.
    return patientMenu.map((item) =>
        item.path === '/inicio' ? { ...item, path: '/dashboard' } : item,
    );
}
