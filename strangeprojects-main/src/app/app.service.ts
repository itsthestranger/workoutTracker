import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';


@Injectable({
  providedIn: 'root'
})
export class AppService {

  loggedIn:boolean = false;
  loggedInChange: Subject<boolean> = new Subject<boolean>();

  constructor() {
    this.loggedInChange.subscribe(value => {
      this.loggedIn = value;
    });
   }

  toggleLoggedIn(){
    this.loggedInChange.next(!this.loggedIn);
  }

  setLoggedIn(value:boolean):void{
    this.loggedInChange.next(value);
    //this.loggedIn = value;
  }
  getLoggedIn():boolean{
    return this.loggedIn;
  }
}
