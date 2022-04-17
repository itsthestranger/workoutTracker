import { Injectable } from '@angular/core';
import { Observable, of, throwError } from "rxjs";
import { catchError, map, tap } from "rxjs/operators";
import { HttpClient, HttpHeaders, HttpParams } from "@angular/common/http";

@Injectable({
  providedIn: 'root'
})
export class FilesService {

  private uploadImageUrl = "https://strangeprojects-api.herokuapp.com/angular_image_upload";
  private uploadExerciseVisualUrl = "https://strangeprojects-api.herokuapp.com/angular_exercise_visual_upload";

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  constructor(private http: HttpClient) { }

  uploadImage(file:File):Observable<any>{
    console.log("in upload  image")
    const formData: FormData = new FormData();
    formData.append('fileKey', file, file.name);
    let token = localStorage.getItem('token');
    if(!token){
      throwError("uploadImage: not Token in localStorage");
    }else{
      formData.append('token', token);
    }
    return this.http.post(this.uploadImageUrl, formData).pipe(
      tap(_ => console.log('uploaded image')),
      catchError(this.handleError<any>('uploadImage', [])));
  }

  uploadExerciseVisual(file:File, exercise_id:number):Observable<any>{
    console.log("in upload exercise visual")
    const formData: FormData = new FormData();
    formData.append('fileKey', file, file.name);
    formData.append('exercise_id', exercise_id.toString());
    let token = localStorage.getItem('token');
    if(!token){
      throwError("uploadExerciseVisual: not Token in localStorage");
    }else{
      formData.append('token', token);
    }
    return this.http.post(this.uploadExerciseVisualUrl, formData).pipe(
      tap(_ => console.log('uploaded visual')),
      catchError(this.handleError<any>('uploadExerciseVisual', [])));
  }

  handleError<T>(operation = 'operation', result?: T){
    return (error: any): Observable<T> => {
      console.error(error);
      console.log(`ERROR: ${operation} failed: ${error.message}`);
      return of(result as T);
    };
  }
}
