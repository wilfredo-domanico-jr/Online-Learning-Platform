import client from './client';

export async function fetchCategories() {
    const { data } = await client.get('/api/v1/categories');
    return data.data;
}

export async function fetchCourses(params = {}) {
    const { data } = await client.get('/api/v1/courses', { params });
    return data;
}

export async function fetchCourse(slug) {
    const { data } = await client.get(`/api/v1/courses/${slug}`);
    return data.data;
}

export async function fetchCourseCurriculum(slug) {
    const { data } = await client.get(`/api/v1/courses/${slug}/curriculum`);
    return data.data;
}
