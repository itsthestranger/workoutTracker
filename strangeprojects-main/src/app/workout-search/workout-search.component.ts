import { Component, OnInit } from '@angular/core';
import { Workout } from "../workout";
import { Observable, Subject } from "rxjs";
import { debounceTime, distinctUntilChanged, switchMap } from "rxjs/operators";
import { WorkoutService } from '../workout.service';

@Component({
  selector: 'app-workout-search',
  templateUrl: './workout-search.component.html',
  styleUrls: ['./workout-search.component.css']
})
export class WorkoutSearchComponent implements OnInit {

  workouts$!: Observable<Workout[]>;
  private searchTerms = new Subject<string>();


  constructor(private workoutService:WorkoutService) { }

  ngOnInit(): void {
    this.workouts$ = this.searchTerms.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((term: string) => this.workoutService.searchWorkout(term)),
    );
  }

  search(term: string): void{
    this.searchTerms.next(term);
  }

}
