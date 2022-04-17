import { Component, NgModule, OnInit } from '@angular/core';
import { Workout } from '../workout';
import { WorkoutService } from '../workout.service';
import { Workoutplan } from "../workoutplan";
import { WorkoutplanService } from '../workoutplan.service';
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from "../app.service";
import { TrackerService } from '../tracker.service';

@Component({
  selector: 'app-workoutplans',
  templateUrl: './workoutplans.component.html',
  styleUrls: ['./workoutplans.component.css']
})
export class WorkoutplansComponent implements OnInit {

  workoutplans:Workoutplan[] = [];
  selected_duration:number = 1;
  weeks: number[] = [];
  
  displayBuild:boolean = false;

  constructor(
    private workoutplanService: WorkoutplanService,
    private workoutService: WorkoutService,
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private appService: AppService,
    private trackerService: TrackerService
  ) {
    this.appService.loggedInChange.subscribe(value => {
      if(!value){
        console.log("not logged in...");
        this.router.navigate(["/login"]);
      }
    });
   }

  ngOnInit(): void {
    if(!localStorage.getItem('token')){
      this.router.navigate(["/login"]);
    }else{
      this.accountService.isLoggedIn().subscribe(ret => {
        if(!ret.loggedIn){
          this.router.navigate(["/login"]);
        }
      });
      this.weeks = Array(52).fill(1).map((x,i) => i + 1);
      this.getWorkoutplans();
    }
  }

  createWorkoutplan(name: string, duration:number): void{
    name = name.trim();
    if (!name){
      return;
    }
    console.log(`createWorkoutplan called with: ${name}, ${duration}`)

    this.workoutplanService.createWorkoutplan(name, duration).subscribe((ret) => this.router.navigate(['/workoutplan_builder/0']));

    
  }

  getWorkoutplans(){
    this.workoutplanService.getWorkoutplans().subscribe(workoutplans => {
      this.workoutplans = workoutplans;
      this.workoutplans.forEach(plan => {
        plan.weeks.forEach(week => {
          week.days.sort((a, b) => (a.day > b.day) ? 1 : -1);
        });
      });
    });
  }

  delete(workoutplan: Workoutplan): void {
    this.workoutplans = this.workoutplans.filter(h => h !== workoutplan);
    this.workoutplanService.deleteWorkoutplan(workoutplan.id).subscribe();
  }

  activate(workoutplan: Workoutplan): void {
    this.trackerService.activateWorkoutplan(workoutplan.id).subscribe();
  }

  toggleBuild(){
    if(this.displayBuild){
      this.displayBuild = false;
    }else{
      this.displayBuild = true;
    }
  }

}
