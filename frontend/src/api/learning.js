import client, { ensureCsrfCookie } from './client';

export async function fetchMyEnrollments(params = {}) {
    const { data } = await client.get('/api/v1/my/enrollments', { params });
    return data;
}

export async function enrollInCourse(slug) {
    await ensureCsrfCookie();
    const { data } = await client.post(`/api/v1/courses/${slug}/enroll`);
    return data.data;
}

export async function fetchLearnCurriculum(slug) {
    const { data } = await client.get(`/api/v1/courses/${slug}/curriculum`);
    return { course: data.data, enrollment: data.enrollment };
}

export async function completeLesson(lessonId) {
    await ensureCsrfCookie();
    const { data } = await client.post(`/api/v1/lessons/${lessonId}/complete`);
    return data;
}

export async function saveLessonProgress(lessonId, lastPositionSeconds) {
    await ensureCsrfCookie();
    await client.post(`/api/v1/lessons/${lessonId}/progress`, {
        last_position_seconds: Math.floor(lastPositionSeconds),
    });
}
