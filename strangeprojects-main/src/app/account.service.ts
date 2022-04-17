import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, of, throwError } from 'rxjs';
import { catchError, debounceTime, map, tap } from "rxjs/operators";
import { Cookie } from "./cookie";
import { LoginResponse } from './login-response';

@Injectable({
  providedIn: 'root'
})
export class AccountService {

  private loginUrl = "https://strangeprojects-api.herokuapp.com/login";
  private registerUrl = "https://strangeprojects-api.herokuapp.com/register";
  //private loginUrl = "http://localhost/strangeprojects/angular_api/login";
  //private isLoggedInUrl = "http://localhost/strangeprojects/angular_api/is_logged_in?";
  private isLoggedInUrl = "https://strangeprojects-api.herokuapp.com/is_logged_in?";
  //private checkPasswordUrl = "http://localhost/strangeprojects/angular_api/check_password?";
  private checkPasswordUrl = "https://strangeprojects-api.herokuapp.com/check_password?";
  private logoutUrl = "https://strangeprojects-api.herokuapp.com/logout?token=";
  //private logoutUrl = "http://localhost/strangeprojects/angular_api/logout?token=";
  //private registerUrl = "http://localhost/strangeprojects/angular_api/register";
  //private updateCookieUrl = "http://localhost/strangeprojects/angular_api/update_cookies";
  private updateCookieUrl = "https://strangeprojects-api.herokuapp.com/update_cookies";
  private checkUsernameAvailUrl = "https://strangeprojects-api.herokuapp.com/check_username_avail?username=";
  //private checkUsernameAvailUrl = "http://localhost/strangeprojects/angular_api/check_username_avail?username=";
  private checkEmailAvailUrl = "https://strangeprojects-api.herokuapp.com/check_email_avail?email=";
  //private checkEmailAvailUrl = "http://localhost/strangeprojects/angular_api/check_email_avail?email=";
  private changePasswordUrl = "https://strangeprojects-api.herokuapp.com/angular_password_change";
  //private changePasswordUrl = "http://localhost/strangeprojects/angular_api/angular_password_change";

  private loggedInUser:number = 0;

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };
  constructor(private http: HttpClient) { }

  rotateChar(char:number):number{
    const rotator = 64;
    for (let index = 0; index < rotator; index++) {
      if (char == 126){
        char = 33;
      }else{
        char += 1;
      }
      //console.log(`char: ${char}, index: ${index}`)
    }
    return char;
  }

  login(username: string, password: string):Observable<LoginResponse>{
    var ip = "192.168.1.8";
   /*TODO -> GET ACTUAL IP DO NOT USE STATIC ONE 
   
   this.http.get("http://api.ipify.org/?format=json").subscribe((res:any)=>{

      ip = res.ip;
      console.log(`ip: ${ip}`)
    });*/
    var user_verification = "";

    for (let index = 0; index < ip.length; index++) {
      var element = ip.charCodeAt(index);
      //console.log(String.fromCharCode(element));
      element = this.rotateChar(element);
      //console.log(String.fromCharCode(element));
      user_verification += String.fromCharCode(element);
    }
    console.log(`user_verification: ${user_verification}`);
    return this.http.post<LoginResponse>(this.loginUrl, {username, password, user_verification})
      .pipe(
        tap((loginResponse: LoginResponse) => {
          this.loggedInUser = loginResponse.user_id;
          console.log(`login response: ${loginResponse.redirect}`);
          console.log(`logged in user: ${this.loggedInUser}`)}),
        catchError(this.handleError<LoginResponse>('login'))
      );
  }

  logout():Observable<any>{
    var ip = "192.168.1.8";
   /*TODO -> GET ACTUAL IP DO NOT USE STATIC ONE 
   
   this.http.get("http://api.ipify.org/?format=json").subscribe((res:any)=>{

      ip = res.ip;
      console.log(`ip: ${ip}`)
    });*/
    var user_verification = "";

    for (let index = 0; index < ip.length; index++) {
      var element = ip.charCodeAt(index);
      //console.log(String.fromCharCode(element));
      element = this.rotateChar(element);
      //console.log(String.fromCharCode(element));
      user_verification += String.fromCharCode(element);
    }
    var token = localStorage.getItem('token');
    localStorage.removeItem('token');
    return this.http.delete<any>(this.logoutUrl + token)
      .pipe(
        tap(ret => console.log(`logout response: ${ret}`)),
        catchError(this.handleError<any>('logout'))
      );
  }

  register(username: string, email: string, password: string):Observable<any>{
    return this.http.post<any>(this.registerUrl, {username, email, password})
      .pipe(
        tap(ret => console.log(`register response: ${ret}`)),
        catchError(this.handleError<LoginResponse>('login'))
      );
  }

  updateCookies(token:string):Observable<LoginResponse>{
    return this.http.post<LoginResponse>(this.updateCookieUrl, {token})
      .pipe(
        tap((loginResponse: LoginResponse) => console.log(`update cookies response: ${loginResponse.redirect}`)),
        catchError(this.handleError<LoginResponse>('updateCookies'))
      );
  }

  handleError<T>(operation = 'operation', result?: T){
    return (error: any): Observable<T> => {
      console.error(error);
      console.log(`ERROR: ${operation} failed: ${error.message}`);
      return of(result as T);
    };
  }

  checkPassword(password:string){
    let token = localStorage.getItem('token');
    if(!token){
      throwError("checkPassword: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('password', password).set('token', token);
    const url = this.checkPasswordUrl + params;
    return this.http.get<any>(url)
      .pipe(
        tap(pass => console.log(`check password response: ${pass.correct}`)),
        catchError(this.handleError<any>('checkPassword')));
  }

  checkUsernameAvailability(username: string){
    return this.http.get<any>(this.checkUsernameAvailUrl + username)
      .pipe(
        tap(res => console.log(`get username avail response: ${res.available}`)),
        catchError(this.handleError<any>('checkusernameavailability'))
      );
  }
  checkEmailAvailability(email: string){
    return this.http.get<any>(this.checkEmailAvailUrl + email)
      .pipe(
        tap(res => console.log(`get email avail response: ${res.available}`)),
        catchError(this.handleError<any>('checkemailavailability'))
      );
  }
  isLoggedIn():Observable<any>{
   
    var ip = "192.168.1.8";
   /*TODO -> GET ACTUAL IP DO NOT USE STATIC ONE 
   
   this.http.get("http://api.ipify.org/?format=json").subscribe((res:any)=>{

      ip = res.ip;
      console.log(`ip: ${ip}`)
    });*/
    var user_verification = "";

    for (let index = 0; index < ip.length; index++) {
      var element = ip.charCodeAt(index);
      //console.log(String.fromCharCode(element));
      element = this.rotateChar(element);
      //console.log(String.fromCharCode(element));
      user_verification += String.fromCharCode(element);
    }
    var token = localStorage.getItem('token');
    let params;
    if(token != null)
      params = new HttpParams().set('user_verification', user_verification).set('token', token);
    //console.log(`isLoggedIn token: ${token}`)
    //console.log({user_verification,token})
    return this.http.get<any>(this.isLoggedInUrl + params)
      .pipe(
        tap(ret => {
          console.log(`isLoggedIn ret: ${ret.loggedIn}`);
        }),
        catchError(this.handleError<any>('isLoggedIn'))
      );
    
    /*
    if(this.doesCookieExist("SPID")){
      if(this.doesCookieExist("SPID_")){
        const cookie_val = document.cookie
          .split('; ')
          .find(row => row.startsWith('SPID='))!
          .split('=')[1];
        console.log(cookie_val);
        console.log("checking if loggedIn")
        this.http.get<any>(this.isLoggedInUrl + cookie_val)
          .pipe(
            tap(ret => {
              console.log(`isLoggedIn ret: ${ret}`);
            }),
            catchError(this.handleError<any>('isLoggedIn'))
          ).subscribe(ret => {
            if(ret){
              if(ret.loggedIn){
                return true;
              }
            }
            return false; 
          })
      }
    }
    return false;*/

  }

  changePassword(password: string): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("changePassword: not Token in localStorage");
    }
    return this.http.put(this.changePasswordUrl, {password, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`updated password`)),
        catchError(this.handleError<any>('changePassword'))
      );
  }
  doesCookieExist(name:string):boolean{
    if(document.cookie.split('; ').find(row => row.startsWith(`${name}=`)) == undefined){
      console.log(`cookie ${name} not found!`);
      return false;
    }
    return true;
  }
}
