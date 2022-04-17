import { Cookie } from "./cookie";
export interface LoginResponse{
    redirect: string;
    user_id:number;
    valid:boolean;//valid username & password
    token:string;
}