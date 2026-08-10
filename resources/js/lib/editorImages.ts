function csrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function uploadEditorImage(file: File): Promise<string> {
    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch('/admin/editor-uploads', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken(),
        },
        body: formData,
    });

    if (!response.ok) {
        throw new Error('Не удалось загрузить изображение.');
    }

    const data = (await response.json()) as { url: string };

    return data.url;
}

export async function insertUploadedImage(
    insert: (attrs: { src: string; alt?: string }) => void,
    file: File,
): Promise<void> {
    const url = await uploadEditorImage(file);
    insert({ src: url, alt: '' });
}
