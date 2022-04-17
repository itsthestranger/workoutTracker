import { Exercise } from "./exercise";

export interface ApiRequest{
    data: null | number | string | Exercise;
    token: string;
}