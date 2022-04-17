import { Injectable } from '@angular/core';
import { Observable, of, throwError } from "rxjs";
import { catchError, map, tap } from "rxjs/operators";
import { HttpClient, HttpHeaders, HttpParams } from "@angular/common/http";
import { UserInformation } from './user_information';


@Injectable({
  providedIn: 'root'
})
export class UserService {

  //private uploadImageUrl = "http://localhost/strangeprojects/angular_api/angular_image_upload";
  //private uploadImageUrl = "https://strangeprojects-api.herokuapp.com/angular_image_upload";
  //private getUserInformationUrl = "http://localhost/strangeprojects/angular_api/angular_user_information?token=";
  private getUserInformationUrl = "https://strangeprojects-api.herokuapp.com/angular_user_information?token=";
  //private getOtherUserInformationUrl = "http://localhost/strangeprojects/angular_api/angular_other_user_information?";
  private getOtherUserInformationUrl = "https://strangeprojects-api.herokuapp.com/angular_other_user_information?";
  //private updateProfileUrl = "http://localhost/strangeprojects/angular_api/angular_user_profile_update";
  private updateProfileUrl = "https://strangeprojects-api.herokuapp.com/angular_user_profile_update";
  //private searchUsersUrl = "http://localhost/strangeprojects/angular_api/angular_user_search?search_term=";
  private searchUsersUrl = "https://strangeprojects-api.herokuapp.com/angular_user_search?search_term=";

  private followUrl = "https://strangeprojects-api.herokuapp.com/angular_follow_user";
  private unfollowUrl = "https://strangeprojects-api.herokuapp.com/angular_unfollow_user?";

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  constructor(private http: HttpClient) { }

  /*uploadImage(file:File):Observable<any>{
    console.log("in upload image")
    const formData: FormData = new FormData();
    formData.append('fileKey', file, file.name);
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getWorkout: not Token in localStorage");
    }else{
      formData.append('token', token);
    }
    return this.http.post(this.uploadImageUrl, formData).pipe(
      tap(_ => console.log('uploaded image')),
      catchError(this.handleError<any>('uploadImage', [])));
  }*/

  getUserInformation():Observable<UserInformation>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getUserInformation: not Token in localStorage");
    }
    
    return this.http.get<UserInformation>(this.getUserInformationUrl + token)
      .pipe(
        tap(_ => console.log(`fetched user information`)),
        catchError(this.handleError<UserInformation>('getUserInformation')));
  }

  getOtherUserInformation(user_id:number):Observable<UserInformation>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getOtherUserInformation: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('user_id', user_id).set('token', token);
    const url = this.getOtherUserInformationUrl + params;
    return this.http.get<UserInformation>(url)
      .pipe(
        tap(_ => console.log(`fetched user information id: ${user_id}`)),
        catchError(this.handleError<UserInformation>('getOtherUserInformation')));
  }

  

  updateProfile(user_profile: UserInformation): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("updateProfile: not Token in localStorage");
    }
    return this.http.put(this.updateProfileUrl, {user_profile, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`updated user id = ${user_profile.id}`)),
        catchError(this.handleError<any>('updateProfile'))
      );
  }

  searchUsers(term: string): Observable<UserInformation[]>{
    if (!term.trim()){
      return of([]);
    }
    const url = this.searchUsersUrl + term;
    return this.http.get<UserInformation[]>(url)
      .pipe(
        tap(x => x.length ? 
          console.log(`found users matching "${term}"`):
          console.log(`no users matching "${term}"`)),
        catchError(this.handleError<UserInformation[]>('searchUsers', []))
      );
  }

  follow(user_id: number): Observable<UserInformation>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("follow: not Token in localStorage");
    }
    return this.http.post<UserInformation>(this.followUrl, {user_id, token})
      .pipe(
        tap(_ => console.log(`following user id = ${user_id}`)),
        catchError(this.handleError<UserInformation>('follow'))
      );
  }

  unfollow(user_id: number): Observable<UserInformation>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("unfollow: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('user_id', user_id).set('token', token);

    const url = this.unfollowUrl + params;
    return this.http.delete<UserInformation>(url,this.httpOptions)
      .pipe(
        tap(_ => console.log(`unfollowed user id = ${user_id}`)),
        catchError(this.handleError<UserInformation>('unfollow'))
      );
  }

  handleError<T>(operation = 'operation', result?: T){
    return (error: any): Observable<T> => {
      console.error(error);
      console.log(`ERROR: ${operation} failed: ${error.message}`);
      return of(result as T);
    };
  }
}
