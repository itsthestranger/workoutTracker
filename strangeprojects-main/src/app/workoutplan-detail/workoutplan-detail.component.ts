import { Component, OnInit } from '@angular/core';
import { Workoutplan } from '../workoutplan';
import { WorkoutService } from "../workout.service";
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from "@angular/common";
import { WorkoutplanService } from "../workoutplan.service";

@Component({
  selector: 'app-workoutplan-detail',
  templateUrl: './workoutplan-detail.component.html',
  styleUrls: ['./workoutplan-detail.component.css']
})
export class WorkoutplanDetailComponent implements OnInit {

  workoutplan?:Workoutplan;

  constructor(
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private workoutplanService: WorkoutplanService,
    private workoutService: WorkoutService,
    private location: Location
  ) { }

  ngOnInit(): void {
    if(!localStorage.getItem('token')){
      this.router.navigate(["/login"]);
    }else{
      this.accountService.isLoggedIn().subscribe(ret => {
        if(!ret.loggedIn){
          this.router.navigate(["/login"]);
        }
      });
      this.getWorkoutplan();
    }
  }

  getWorkoutplan(){
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.workoutplanService.getWorkoutplan(id).subscribe(workoutplan => {
      this.workoutplan = workoutplan;
      this.workoutplan.weeks.forEach(week => {
        week.days.sort((a, b) => (a.day > b.day) ? 1 : -1);
      });
    });
  }

  edit(){
    //redirect to workoutplan-builder
    let route = '/workoutplan_builder/' + this.workoutplan?.id;
    this.router.navigate([route]);
  }
  duplicate(): void{
    if (this.workoutplan){
      console.log(`workoutplan: ${this.workoutplan}`);
      this.workoutplanService.duplicateWorkoutplan(this.workoutplan).subscribe(() => this.goBack());
    }
  }

  goBack(): void{
    this.location.back();
  }
}
