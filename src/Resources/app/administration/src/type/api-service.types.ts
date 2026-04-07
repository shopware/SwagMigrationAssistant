import type { AxiosInstance, AxiosResponse } from 'axios';

export type ApiResponse<T> = T extends null | undefined ? AxiosResponse<T> : T;

export type AdditionalHeaders = Record<string, string>;

export type ActionResponse = ApiResponse<null>;

export type ApiServiceBase = {
    httpClient: AxiosInstance;
    name: string;
    getBasicHeaders(additionalHeaders?: AdditionalHeaders): AdditionalHeaders;
    getApiBasePath(id?: string | number, prefix?: string): string;
};
