import { Injectable } from '@angular/core';
import { Observable, of, throwError } from "rxjs";
import { catchError, map, tap } from "rxjs/operators";
import { HttpClient, HttpHeaders, HttpParams } from "@angular/common/http";
import { Workoutplan } from './workoutplan';
import { WorkoutplanWeek } from "./workoutplan_week";

@Injectable({
  providedIn: 'root'
})
export class WorkoutplanService {

  //private createWorkoutplanUrl = "http://localhost/strangeprojects/angular_api/angular_create_workoutplan";
  private createWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_create_workoutplan";
  private duplicateWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplan_duplicate";
  //private getWorkoutplansUrl = "http://localhost/strangeprojects/angular_api/angular_workoutplans?token=";
  private getWorkoutplansUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplans?token=";
  //private getWorkoutplanUrl = "http://localhost/strangeprojects/angular_api/angular_workoutplan?";
  private getWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplan?";
  //private buildWorkoutplanUrl = "http://localhost/strangeprojects/angular_api/angular_build_workoutplan?token=";
  private buildWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_build_workoutplan?token=";
  //private editWorkoutplanUrl = "http://localhost/strangeprojects/angular_api/angular_edit_workoutplan?";
  private editWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_edit_workoutplan?";
  //private updateWorkoutplanUrl = "http://localhost/strangeprojects/angular_api/angular_workoutplan_update";
  private updateWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplan_update";
  //private deleteWorkoutplanUrl = "http://localhost/strangeprojects/angular_api/angular_workoutplan_delete?";
  private deleteWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplan_delete?";
  //private addWeekUrl = "http://localhost/strangeprojects/angular_api/angular_workoutplan_add_week";
  private addWeekUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplan_add_week";
  //private removeWeekUrl = "http://localhost/strangeprojects/angular_api/angular_workoutplan_remove_week?";
  private removeWeekUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplan_remove_week?";

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  constructor(private http: HttpClient) { }

  createWorkoutplan(name: string, duration:number): Observable<Workoutplan>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("createWorkoutplan: not Token in localStorage");
    }
    return this.http.post<Workoutplan>(this.createWorkoutplanUrl, {name, duration, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`created new workoutplan duration=${duration}`)),
        catchError(this.handleError<Workoutplan>('createWorkoutplan'))
      );
  }

  buildWorkoutplan(): Observable<Workoutplan>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("buildWorkoutplan: not Token in localStorage");
    }
    return this.http.get<Workoutplan>(this.buildWorkoutplanUrl + token)
      .pipe(
        tap(_ => console.log(`get base values for new workoutplan`)),
        catchError(this.handleError<Workoutplan>('buildWorkoutplan'))
      );
  }
  editWorkoutplan(id:number): Observable<Workoutplan>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("editWorkoutplan: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('workoutplan_id', id).set('token', token);
    const url = this.editWorkoutplanUrl + params;
    return this.http.get<Workoutplan>(url)
      .pipe(
        tap(_ => console.log(`get base values for new workoutplan`)),
        catchError(this.handleError<Workoutplan>('editWorkoutplan'))
      );
  }

  getWorkoutplans(): Observable<Workoutplan[]>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getWorkoutplans: not Token in localStorage");
    }
    return this.http.get<Workoutplan[]>(this.getWorkoutplansUrl + token)
      .pipe(
        tap(_ => console.log(`get workoutplans`)),
        catchError(this.handleError<Workoutplan[]>('getWorkoutplans'))
      );
  }

  getWorkoutplan(id: number):Observable<Workoutplan>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getWorkoutplan: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('workoutplan_id', id).set('token', token);
    const url = this.getWorkoutplanUrl + params;
    return this.http.get<Workoutplan>(url)
      .pipe(
        tap(_ => console.log(`fetched workoutplan id: ${id}`)),
        catchError(this.handleError<Workoutplan>(`getWorkoutplan id: ${id}`)));
  }

  updateWorkoutplan(workoutplan: Workoutplan): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("updateWorkoutplan: not Token in localStorage");
    }
    return this.http.put(this.updateWorkoutplanUrl, {workoutplan, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`updated workoutplan id = ${workoutplan.id}`)),
        catchError(this.handleError<any>('updateWorkoutplan'))
      );
  }

  duplicateWorkoutplan(workoutplan: Workoutplan): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("duplicateWorkoutplan: not Token in localStorage");
    }
    return this.http.post(this.duplicateWorkoutplanUrl, {workoutplan, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`duplicated workoutplan id = ${workoutplan.id}`)),
        catchError(this.handleError<any>('duplicateWorkoutplan'))
      );
  }

  deleteWorkoutplan(id: number): Observable<Workoutplan>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("deleteWorkoutplan: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('workoutplan_id', id).set('token', token);
    const url = this.deleteWorkoutplanUrl + params;
    return this.http.delete<Workoutplan>(url, this.httpOptions)
      .pipe(
        tap(_ => console.log(`deleted workoutplan id=${id}`)),
        catchError(this.handleError<Workoutplan>('deleteWorkoutplan'))
      );
  }

  addWeek(workoutplan_id:number): Observable<WorkoutplanWeek>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("addWeek: not Token in localStorage");
    }
    return this.http.post<WorkoutplanWeek>(this.addWeekUrl, {workoutplan_id, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`added week to workoutplan id=${workoutplan_id}`)),
        catchError(this.handleError<WorkoutplanWeek>('addWeek'))
      );
  }
  removeWeek(workoutplan_id:number): Observable<WorkoutplanWeek>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("removeWeek: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('workoutplan_id', workoutplan_id).set('token', token);
    const url = this.removeWeekUrl + params;
    return this.http.delete<WorkoutplanWeek>(url, this.httpOptions)
      .pipe(
        tap(_ => console.log(`removed week from workoutplan id=${workoutplan_id}`)),
        catchError(this.handleError<WorkoutplanWeek>('removeWeek'))
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
