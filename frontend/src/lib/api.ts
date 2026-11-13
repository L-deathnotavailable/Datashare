export type User = { id: string; email: string; createdAt: string }
export type LoginResponse = { accessToken: string; tokenType: 'Bearer'; expiresIn: number }

export class ApiError extends Error {
  status: number
  errors: Record<string, string>

  constructor(status: number, detail: string, errors: Record<string, string> = {}) {
    super(detail)
    this.status = status
    this.errors = errors
  }
}

export async function apiRequest<T>(path: string, options: RequestInit = {}, token?: string): Promise<T> {
  let response: Response
  try {
    response = await fetch(`/api${path}`, {
      ...options,
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...options.headers,
      },
    })
  } catch {
    throw new ApiError(0, 'Connexion impossible. Vérifiez votre réseau puis réessayez.')
  }
  const data = await response.json().catch(() => null)
  if (!response.ok) throw new ApiError(response.status, data?.detail ?? 'Le service est momentanément indisponible.', data?.errors)
  return data as T
}
