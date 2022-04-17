import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from "@angular/common/http";
import { Observable, of, throwError } from 'rxjs';
import { WorkoutTracker } from './workout_tracker';
import { catchError, tap } from 'rxjs/operators';
import { WorkoutplanTracker } from "./workoutplan_tracker";

@Injectable({
  providedIn: 'root'
})
export class TrackerService {

  //private startTrackerUrl = "http://localhost/strangeprojects/angular_api/angular_start_tracker";
  private startTrackerUrl = "https://strangeprojects-api.herokuapp.com/angular_start_tracker";
  //private stopTrackerUrl = "http://localhost/strangeprojects/angular_api/angular_stop_tracker";
  private stopTrackerUrl = "https://strangeprojects-api.herokuapp.com/angular_stop_tracker";

  private getTrackerUrl = "https://strangeprojects-api.herokuapp.com/angular_get_workout_tracker?token=";
  //private getTrackedWorkoutsUrl = "http://localhost/strangeprojects/angular_api/angular_tracked_workouts?token=";
  private getTrackedWorkoutsUrl = "https://strangeprojects-api.herokuapp.com/angular_tracked_workouts?token=";
  //private activateWorkoutplanUrl = "http://localhost/strangeprojects/angular_api/angular_activate_workoutplan";
  private activateWorkoutplanUrl = "https://strangeprojects-api.herokuapp.com/angular_activate_workoutplan";
  //private getWorkoutplanTrackerUrl = "http://localhost/strangeprojects/angular_api/angular_workoutplan_tracker?token=";
  private getWorkoutplanTrackerUrl = "https://strangeprojects-api.herokuapp.com/angular_workoutplan_tracker?token=";

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };
  constructor(private http: HttpClient) { }

  startTracker(workout_id:number, workoutplan_id:number|null, deload:boolean|null):Observable<WorkoutTracker>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("startTracker: not Token in localStorage");
    }
    return this.http.post<WorkoutTracker>(this.startTrackerUrl, {workout_id, workoutplan_id, deload, token})
      .pipe(
        tap(_ => console.log(`started tracker for workout id: ${workout_id}`)),
        catchError(this.handleError<WorkoutTracker>(`startTracker workout id: ${workout_id}`))
      );
  }
  getTracker():Observable<WorkoutTracker>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("startTracker: not Token in localStorage");
    }
    return this.http.get<WorkoutTracker>(this.getTrackerUrl + token)
      .pipe(
        tap(_ => console.log(`get Tracker id: ${_.id}`)),
        catchError(this.handleError<WorkoutTracker>(`getTracker`))
      );
  }
  stopTracker(tracker:WorkoutTracker):Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("stopTracker: not Token in localStorage");
    }
    return this.http.put(this.stopTrackerUrl, {tracker, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`stopped tracker id = ${tracker.id}`)),
        catchError(this.handleError<any>('stopTracker'))
      );
  }

  getTrackedWorkouts():Observable<WorkoutTracker[]>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getTrackedWorkouts: not Token in localStorage");
    }
    return this.http.get<WorkoutTracker[]>(this.getTrackedWorkoutsUrl + token)
      .pipe(
        tap(_ => console.log('fetched tracked workouts')),
        catchError(this.handleError<WorkoutTracker[]>('getTrackedWorkouts', [])));
  }

  activateWorkoutplan(workoutplan_id:number):Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("activateWorkoutplan: not Token in localStorage");
    }
    return this.http.post<any>(this.activateWorkoutplanUrl, {workoutplan_id, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`activated workoutplan id = ${workoutplan_id}`)),
        catchError(this.handleError<any>('activateWorkoutplan'))
      );
  }

  getWorkoutplanTracker():Observable<WorkoutplanTracker>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getWorkoutplanTracker: not Token in localStorage");
    }
    return this.http.get<WorkoutplanTracker>(this.getWorkoutplanTrackerUrl + token)
      .pipe(
        tap(_ => console.log('fetched workoutplan tracker')),
        catchError(this.handleError<WorkoutplanTracker>('getWorkoutplanTracker')));
  }

  handleError<T>(operation = 'operation', result?: T){
    return (error: any): Observable<T> => {
      console.error(error);
      console.log(`ERROR: ${operation} failed: ${error.message}`);
      return of(result as T);
    };
  }
}
