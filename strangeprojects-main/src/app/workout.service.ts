import { Injectable } from '@angular/core';
import { Observable, of, throwError } from "rxjs";
import { catchError, map, tap } from "rxjs/operators";
import { HttpClient, HttpHeaders, HttpParams } from "@angular/common/http";
import { Workout } from "./workout";

@Injectable({
  providedIn: 'root'
})
export class WorkoutService {

  //private workoutsUrl = "http://localhost/strangeprojects/angular_api/angular_workouts?token=";
  private workoutsUrl = "https://strangeprojects-api.herokuapp.com/angular_workouts?token=";
  //private allWorkoutsUrl = "http://localhost/strangeprojects/angular_api/angular_all_workouts?token=";
  private allWorkoutsUrl = "https://strangeprojects-api.herokuapp.com/angular_all_workouts?token=";
  //private workoutUrl = "http://localhost/strangeprojects/angular_api/angular_workout?";
  private workoutUrl = "https://strangeprojects-api.herokuapp.com/angular_workout?";
  //private updateWorkoutUrl = "http://localhost/strangeprojects/angular_api/angular_workout_update";
  private updateWorkoutUrl = "https://strangeprojects-api.herokuapp.com/angular_workout_update";
  //private duplicateWorkoutUrl = "http://localhost/strangeprojects/angular_api/angular_workout_duplicate";
  private duplicateWorkoutUrl = "https://strangeprojects-api.herokuapp.com/angular_workout_duplicate";
  //private deleteWorkoutUrl = "http://localhost/strangeprojects/angular_api/angular_workout_delete?";
  private deleteWorkoutUrl = "https://strangeprojects-api.herokuapp.com/angular_workout_delete?";
  //private deleteExerciseUrl = "http://localhost/strangeprojects/angular_api/angular_workout_exercise_delete?";
  private deleteExerciseUrl = "https://strangeprojects-api.herokuapp.com/angular_workout_exercise_delete?";
  //private addWorkoutUrl = "http://localhost/strangeprojects/angular_api/angular_workout_add";
  private addWorkoutUrl = "https://strangeprojects-api.herokuapp.com/angular_workout_add";
  //private searchWorkoutsUrl = "http://localhost/strangeprojects/angular_api/angular_workout_search?search_term=";
  private searchWorkoutsUrl = "https://strangeprojects-api.herokuapp.com/angular_workout_search?";

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  constructor(private http: HttpClient) { }

  getWorkouts():Observable<Workout[]>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getWorkouts: not Token in localStorage");
    }
    return this.http.get<Workout[]>(this.workoutsUrl + token)
      .pipe(
        tap(_ => console.log('fetched workouts')),
        catchError(this.handleError<Workout[]>('getWorkouts', [])));
  }

  /*get all workouts -> same as getWorkouts + Rest day*/
  getAllWorkouts():Observable<Workout[]>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getAllWorkouts: not Token in localStorage");
    }
    return this.http.get<Workout[]>(this.allWorkoutsUrl + token)
      .pipe(
        tap(_ => console.log('fetched all workouts')),
        catchError(this.handleError<Workout[]>('getAllWorkouts', [])));
  }
  getWorkout(id: number):Observable<Workout>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getWorkout: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('workout_id', id).set('token', token);
    const url = this.workoutUrl + params;
    return this.http.get<Workout>(url)
      .pipe(
        tap(_ => console.log(`fetched workout id: ${id}`)),
        catchError(this.handleError<Workout>(`getWorkout id: ${id}`)));
  }

  updateWorkout(workout: Workout): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("updateWorkout: not Token in localStorage");
    }
    return this.http.put(this.updateWorkoutUrl, {workout, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`updated workout id = ${workout.id}`)),
        catchError(this.handleError<any>('updateWorkout'))
      );
  }

  duplicateWorkout(workout: Workout): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("duplicateWorkout: not Token in localStorage");
    }
    return this.http.post(this.duplicateWorkoutUrl, {workout, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`duplicated workout id = ${workout.id}`)),
        catchError(this.handleError<any>('duplicateWorkout'))
      );
  }

  deleteWorkout(id: number): Observable<Workout>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("deleteWorkout: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('workout_id', id).set('token', token);
    const url = this.deleteWorkoutUrl + params;
    return this.http.delete<Workout>(url, this.httpOptions)
      .pipe(
        tap(_ => console.log(`deleted workout id=${id}`)),
        catchError(this.handleError<Workout>('deleteWorkout'))
      );
  }

  deleteExercise(workout_id:number,exercise_id: number): Observable<Workout>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("deleteExercise: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('workout_id', workout_id).set('exercise_id', exercise_id).set('token', token);
    const url = this.deleteExerciseUrl + params;
    return this.http.delete<Workout>(url, this.httpOptions)
      .pipe(
        tap(_ => console.log(`deleted exercise id=${exercise_id} from workout id=${workout_id}`)),
        catchError(this.handleError<Workout>('deleteExercise'))
      );
  }

  addWorkout(workout: Workout): Observable<Workout>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("addWorkout: not Token in localStorage");
    }
    return this.http.post<Workout>(this.addWorkoutUrl, {workout, token}, this.httpOptions)
      .pipe(
        tap((newWorkout: Workout) => {console.log(`added workout w/ id=${newWorkout.id}`);console.log(newWorkout)}),
        catchError(this.handleError<Workout>('addWorkout'))
      );
  }

  searchWorkout(search_term: string): Observable<Workout[]>{
    if (!search_term.trim()){
      return of([]);
    }
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getWorkout: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('search_term', search_term).set('token', token);
    const url = this.searchWorkoutsUrl + params;
    return this.http.get<Workout[]>(url)
      .pipe(
        tap(x => x.length ? 
          console.log(`found workouts matching "${search_term}"`):
          console.log(`no workouts matching "${search_term}"`)),
        catchError(this.handleError<Workout[]>('searchWorkouts', []))
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
