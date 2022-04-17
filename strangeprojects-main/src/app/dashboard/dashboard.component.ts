import { Component, OnInit } from '@angular/core';
import { Exercise } from "../exercise";
import { ExerciseService } from "../exercise.service";
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from "../app.service";

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  exercises: Exercise[] = [];
  constructor(
    private exerciseService: ExerciseService, 
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private appService: AppService
    ) {
      this.appService.loggedInChange.subscribe(value => {
        if(!value){
          console.log("not logged in...");
          this.router.navigate(["/login"]);
        }
      });
    }

  ngOnInit(): void {
    /*if(localStorage.getItem('token')){
      return true;
    }else{
      return false;
    }*/
    if(!localStorage.getItem('token')){
      this.router.navigate(["/login"]);
    }else{
      this.accountService.isLoggedIn().subscribe(ret => {
        if(!ret.loggedIn){
          this.router.navigate(["/login"]);
        }
      });

      this.getExercises();
    }
  }
  getExercises(): void{
    this.exerciseService.getExercises()
      .subscribe(exercises => this.exercises = exercises.slice(1,5));
  }
}
