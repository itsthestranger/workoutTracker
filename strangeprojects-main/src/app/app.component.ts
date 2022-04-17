import { Component, OnInit } from '@angular/core';
import { AccountService } from "./account.service";
import { AppService } from "./app.service";
import { ExerciseService } from "./exercise.service";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit{
  title = 'strangeprojects';
  
  constructor(public accountService:AccountService,
    public appService: AppService,
    private exerciseService: ExerciseService){
      this.appService.loggedInChange.subscribe(value => this.loggedIn = value)
    }

  loggedIn: boolean = false;

  ngOnInit(): void {

    //this.exerciseService.getShit().subscribe();
    //console.log("initing...");

    if(localStorage.getItem('token')){
      this.accountService.isLoggedIn().subscribe(ret => {
        if(ret.loggedIn){
          this.appService.setLoggedIn(true);
          //this.loggedIn = true;
        }
      });
    }
  }

  logout(){
    if(localStorage.getItem('token')){
      this.accountService.logout().subscribe();
      this.appService.setLoggedIn(false);
      //this.loggedIn = false;
    }else{
      this.appService.setLoggedIn(false);
      //this.loggedIn = false;
    }

  }
}
