import { Component, OnInit, OnChanges } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AccountService } from '../account.service';
import { first } from 'rxjs/operators';
import { Cookie } from '../cookie';
import { flatten } from '@angular/compiler';
import { AppService } from "../app.service";

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {
  form: FormGroup;
  loading = false;
  submitted = false;

  valid:boolean = true;


  constructor(
    private formBuilder: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private accountService: AccountService,
    private appService: AppService,
  ) {
    this.form = this.formBuilder.group({
      username: ['', Validators.required],
      password: ['', Validators.required]
    });
   }
  
  doesCookieExist(name:string):boolean{
    if(document.cookie.split('; ').find(row => row.startsWith(`${name}=`)) == undefined){
      console.log(`cookie ${name} not found!`);
      return false;
    }
    return true;
  }

  ngOnInit(): void {

    if(localStorage.getItem('token')){
      this.accountService.isLoggedIn().subscribe(ret => {
        if(ret.loggedIn){
          this.router.navigate(["/dashboard"]);
        }
      });
    }
    //TODO: redirect to HOME if already logged in!  
    //this.doesCookieExist("SPID");
    //this.doesCookieExist("SPID_");
    /*if(this.doesCookieExist("SPID")){
      if(this.doesCookieExist("SPID_")){
        console.log("both cookies set! redirecting to home ...");
        this.router.navigate(['/dashboard']);
      }else{
        console.log("gotta reset the cookies!");
        //get spid cookie
        const cookie_val = document.cookie
          .split('; ')
          .find(row => row.startsWith('SPID='))!
          .split('=')[1];
        console.log(cookie_val);
        this.accountService.updateCookies(cookie_val)
          .subscribe(
            loginResponse => {
              //console.log(`response: cookie_spid: ${loginResponse.cookie_spid.name}`);
              //console.log(`response: cookie_spid_: ${loginResponse.cookie_spid_.name}`);
              //console.log(`response: redirect: ${loginResponse.redirect}`);
              this.setCookie(loginResponse.cookie_spid);
              this.setCookie(loginResponse.cookie_spid_);
              this.router.navigate([loginResponse.redirect]);
            }
          );
      }
    }*/
  }
  get f(){
    return this.form.controls;
  }
  setCookie(keks:Cookie):void{
    //console.log(new Date(keks.attributes.expires * 1000));
    document.cookie = `${keks.name}=${keks.value};expires=${keks.attributes.expires};path=${keks.attributes.path};domain:${keks.attributes.domain};secure=${keks.attributes.secure};samesite=${keks.attributes.samesite}`;
  }
  onSubmit():void{
    this.submitted = true;
    this.valid = true;

    if(this.form.invalid){
      return;
    }
    this.loading = true;
    
    this.accountService.login(this.f.username.value, this.f.password.value)
      .subscribe(
        loginResponse => {
          if(!loginResponse.valid){
            //wrong username or password
            this.submitted = false;
            this.loading = false;
            this.valid = false;
          }else{
            console.log(`ret token: ${loginResponse.token}`)
            localStorage.setItem('token', loginResponse.token);
            this.appService.setLoggedIn(true);
            this.router.navigate([loginResponse.redirect]);
          }
        }
      );

  }

}
