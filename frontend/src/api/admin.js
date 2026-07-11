import client, { ensureCsrfCookie } from './client';

export async function fetchInstructorApplications(params = {}) {
    const { data } = await client.get('/api/v1/admin/instructor-applications', { params });
    return data;
}

export async function approveInstructorApplication(id) {
    await ensureCsrfCookie();
    const { data } = await client.post(`/api/v1/admin/instructor-applications/${id}/approve`);
    return data.data;
}

export async function rejectInstructorApplication(id, rejectionReason) {
    await ensureCsrfCookie();
    const { data } = await client.post(`/api/v1/admin/instructor-applications/${id}/reject`, {
        rejection_reason: rejectionReason,
    });
    return data.data;
}

export async function fetchCoursesForModeration(params = {}) {
    const { data } = await client.get('/api/v1/admin/courses', { params });
    return data;
}

export async function approveCourse(id) {
    await ensureCsrfCookie();
    const { data } = await client.post(`/api/v1/admin/courses/${id}/approve`);
    return data.data;
}

export async function rejectCourse(id, rejectionReason) {
    await ensureCsrfCookie();
    const { data } = await client.post(`/api/v1/admin/courses/${id}/reject`, {
        rejection_reason: rejectionReason,
    });
    return data.data;
}
