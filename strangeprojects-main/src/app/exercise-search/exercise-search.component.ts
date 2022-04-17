import { Component, OnInit } from '@angular/core';
import { Observable, Subject } from "rxjs";
import { debounceTime, distinctUntilChanged, switchMap } from "rxjs/operators";
import { Exercise } from "../exercise";
import { ExerciseService } from "../exercise.service";

@Component({
  selector: 'app-exercise-search',
  templateUrl: './exercise-search.component.html',
  styleUrls: ['./exercise-search.component.css']
})
export class ExerciseSearchComponent implements OnInit {
  exercises$!: Observable<Exercise[]>;
  private searchTerms = new Subject<string>();

  constructor(private exerciseService: ExerciseService) { }

  search(term: string): void{
    this.searchTerms.next(term);
  }

  ngOnInit(): void {
    this.exercises$ = this.searchTerms.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((term: string) => this.exerciseService.searchExercises(term)),
    );
  }

}
