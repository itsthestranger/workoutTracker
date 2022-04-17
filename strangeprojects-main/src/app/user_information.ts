export interface UserInformation{
    id: number;
    username: string;
    email:string;
    first_name: string;
    last_name: string;
    profile_picture_path: string;
    own_profile: boolean;
    following:boolean;
}