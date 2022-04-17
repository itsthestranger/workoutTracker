export interface CookieAttributes{
    expires:number;
    path:string;
    domain:string;
    secure:boolean;
    httponly:boolean;
    samesite:string;
}