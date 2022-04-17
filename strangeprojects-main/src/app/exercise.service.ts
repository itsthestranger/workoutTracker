import { Injectable } from '@angular/core';
import { Exercise } from "./exercise";
import { Bodypart } from "./bodypart";
import { Equipment } from "./equipment";
import { ExerciseHistorySet } from "./exercise_history_set";
import { EXERCISES } from "./test-exercises";
import { Observable, of, throwError } from "rxjs";
import { catchError, map, tap } from "rxjs/operators";
import { HttpClient, HttpHeaders, HttpParams } from "@angular/common/http";
import { TrackingUnit } from './tracking_unit';

@Injectable({
  providedIn: 'root'
})
export class ExerciseService {

  private exercisesUrl = "https://strangeprojects-api.herokuapp.com/angular_exercises?token=";
  private bodypartsUrl = "https://strangeprojects-api.herokuapp.com/get_bodyparts?token=";
  private shit = "https://strangeprojects-api.herokuapp.com/shit";
  //private equipmentUrl = "http://localhost/strangeprojects/angular_api/angular_get_equipment?token=";
  private equipmentUrl = "https://strangeprojects-api.herokuapp.com/angular_get_equipment?token=";
  private trackingUnitsUrl = "https://strangeprojects-api.herokuapp.com/angular_get_tracking_units?token=";
  //private exerciseHistoryUrl = "http://localhost/strangeprojects/angular_api/angular_exercise_history?";
  private exerciseHistoryUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise_history?";
  //private exerciseUrl = "http://localhost/strangeprojects/angular_api/angular_exercise?";
  private exerciseUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise?";
  //private updateExerciseUrl = "http://localhost/strangeprojects/angular_api/angular_exercise_update";
  private updateExerciseUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise_update";
  //private duplicateExerciseUrl = "http://localhost/strangeprojects/angular_api/angular_exercise_duplicate";
  private duplicateExerciseUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise_duplicate";
  //private addExerciseUrl = "http://localhost/strangeprojects/angular_api/angular_exercise_add";
  private addExerciseUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise_add";
  //private deleteExerciseUrl = "http://localhost/strangeprojects/angular_api/angular_exercise_delete?";
  private deleteExerciseUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise_delete?";
  //private searchExercisesUrl = "http://localhost/strangeprojects/angular_api/angular_exercise_search?search_term=";
  private searchExercisesUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise_search?";

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  constructor(private http: HttpClient) { }

  getShit(){
  
    //console.log(this.http.get<Exercise[]>(this.exerciseUrl));
    return this.http.get(this.shit)
      .pipe(
        tap(_ => console.log(`fetched shit ${_}`)),
        catchError(this.handleError('getShit')));
  }

  getBodyparts(): Observable<Bodypart[]>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getBodyparts: not Token in localStorage");
    }
    //console.log(this.http.get<Exercise[]>(this.exerciseUrl));
    return this.http.get<Bodypart[]>(this.bodypartsUrl + token)
      .pipe(
        tap(_ => console.log(`fetched bodyparts ${_}`)),
        catchError(this.handleError<Bodypart[]>('getBodyparts', [])));
  }
  getEquipment(): Observable<Equipment[]>{
    //console.log(this.http.get<Exercise[]>(this.exerciseUrl));
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getEquipment: not Token in localStorage");
    }
    return this.http.get<Equipment[]>(this.equipmentUrl + token)
      .pipe(
        tap(_ => console.log('fetched equipment')),
        catchError(this.handleError<Equipment[]>('getEquipment', [])));
  }

  getTrackingUnits(): Observable<TrackingUnit[]>{
    //console.log(this.http.get<Exercise[]>(this.exerciseUrl));
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getTrackingUnits: not Token in localStorage");
    }
    return this.http.get<TrackingUnit[]>(this.trackingUnitsUrl + token)
      .pipe(
        tap(_ => console.log('fetched tracking units')),
        catchError(this.handleError<Equipment[]>('getTrackingUnits', [])));
  }

  getExerciseHistory(id: number): Observable<ExerciseHistorySet[]>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getExerciseHistory: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('exercise_id', id).set('token', token);
    const url = this.exerciseHistoryUrl + params;
    //console.log(`url: ${url}`);
    return this.http.get<ExerciseHistorySet[]>(url)
      .pipe(
        tap(_ => console.log(`fetched exercise history id=${id}`)),
        catchError(this.handleError<ExerciseHistorySet[]>(`getExerciseHistory id=${id}`)));
  }

  getExercises(): Observable<Exercise[]>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getExercises: not Token in localStorage");
    }
    //console.log(this.http.get<Exercise[]>(this.exerciseUrl));
    return this.http.get<Exercise[]>(this.exercisesUrl + token)
      .pipe(
        tap(_ => console.log('fetched exercises')),
        catchError(this.handleError<Exercise[]>('getExercises', [])));
  }

  getExercise(id: number): Observable<Exercise>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getExercise: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('exercise_id', id).set('token', token);
    const url = this.exerciseUrl + params;
    //console.log(`url: ${url}`);
    return this.http.get<Exercise>(url)
      .pipe(
        tap(_ => console.log(`fetched exercise id=${id}`)),
        catchError(this.handleError<Exercise>(`getExercise id=${id}`)));
  }

  updateExercise(exercise: Exercise): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("updateExercise: not Token in localStorage");
    }
    return this.http.put(this.updateExerciseUrl, {exercise, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`updated exercise id = ${exercise.id}`)),
        catchError(this.handleError<any>('updateExercise'))
      );
  }

  duplicateExercise(exercise: Exercise): Observable<any>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("duplicateExercise: not Token in localStorage");
    }
    return this.http.post(this.duplicateExerciseUrl, {exercise, token}, this.httpOptions)
      .pipe(
        tap(_ => console.log(`duplicated exercise id = ${exercise.id}`)),
        catchError(this.handleError<any>('duplicateExercise'))
      );
  }

  addExercise(exercise: Exercise): Observable<Exercise>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("addExercise: not Token in localStorage");
    }
    return this.http.post<Exercise>(this.addExerciseUrl, {exercise, token}, this.httpOptions)
      .pipe(
        tap((newExercise: Exercise) => {console.log(`added exercise w/ id=${newExercise.id}`);console.log(newExercise)}),
        catchError(this.handleError<Exercise>('addExercise'))
      );
  }

  deleteExercise(id: number): Observable<Exercise>{
    let token = localStorage.getItem('token');
    if(!token){
      throwError("deleteExercise: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('exercise_id', id).set('token', token);

    const url = this.deleteExerciseUrl + params;
    return this.http.delete<Exercise>(url, this.httpOptions)
      .pipe(
        tap(_ => console.log(`deleted exercise id=${id}`)),
        catchError(this.handleError<Exercise>('deleteExercise'))
      );
  }

  searchExercises(search_term: string): Observable<Exercise[]>{
    if (!search_term.trim()){
      return of([]);
    }
    let token = localStorage.getItem('token');
    if(!token){
      throwError("getExercise: not Token in localStorage");
    }
    let params;
    if(token != null)
      params = new HttpParams().set('search_term', search_term).set('token', token);
    const url = this.searchExercisesUrl + params;

    return this.http.get<Exercise[]>(url)
      .pipe(
        tap(x => x.length ? 
          console.log(`found exercises matching "${search_term}"`):
          console.log(`no exercises matching "${search_term}"`)),
        catchError(this.handleError<Exercise[]>('searchExercises', []))
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
