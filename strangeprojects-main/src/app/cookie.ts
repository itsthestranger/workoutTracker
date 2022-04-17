import { CookieAttributes } from "./cookie-attributes";
export interface Cookie{
    name: string;
    value: string;
    attributes:CookieAttributes;
}