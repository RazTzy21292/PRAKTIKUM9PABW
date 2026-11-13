export type ValidationErrors = Record<string, string[]>;

type ErrorBody = { message?: string; errors?: ValidationErrors } | null;

export class HttpError extends Error {
    status: number;
    data: ErrorBody;

    constructor(status: number, data: ErrorBody) {
        super(data?.message ?? `Request gagal dengan status ${status}`);
        this.status = status;
        this.data = data;
    }
}

// Membaca token CSRF dari cookie XSRF-TOKEN atau meta csrf-token
function getXsrfToken(): string {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.substring('XSRF-TOKEN='.length)) : '';
}

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
}

type RequestOptions = {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    body?: FormData;
};

export async function http<T>(url: string, { method = 'GET', body }: RequestOptions = {}): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const xsrf = getXsrfToken();
    if (xsrf) {
        headers['X-XSRF-TOKEN'] = xsrf;
    }

    const csrf = getCsrfToken();
    if (csrf) {
        headers['X-CSRF-TOKEN'] = csrf;
    }

    const response = await fetch(url, {
        method,
        body,
        credentials: 'same-origin',
        headers,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        throw new HttpError(response.status, data);
    }

    return data as T;
}
