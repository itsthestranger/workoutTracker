import { Component, OnInit } from '@angular/core';
import { UserService } from '../user.service';
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from "@angular/common";
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { InputMatchValidator, CheckCurrentPassword } from "../custom-validators.validator";

@Component({
  selector: 'app-change-password',
  templateUrl: './change-password.component.html',
  styleUrls: ['./change-password.component.css']
})
export class ChangePasswordComponent implements OnInit {

  form: FormGroup;
  loading = false;
  submitted = false;

  constructor(
    private formBuilder: FormBuilder,
    private userService: UserService,
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private location: Location
    ) { 
      this.form = this.formBuilder.group({
        password_current: ['', Validators.required],
        password_new: ['', [Validators.required, Validators.minLength(6), Validators.maxLength(60), Validators.pattern("^(?=[^A-Z]*[A-Z])(?=\\D*\\d)[^:&.~\\s][a-zA-Z0-9]+$")]], //needs to have at least one uppercase letter and at least one number (special characters are not allowed)
        password_confirm: ['', Validators.required]
        },{
          validator: [
            InputMatchValidator('password_new', 'password_confirm'), 
            CheckCurrentPassword(this.accountService, 'password_current')
          ]
        }
      );
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
      
    }
  }

  get f(){
    return this.form.controls;
  }

  onSubmit(){
    this.submitted = true;

    if(this.form.invalid){
      return;
    }
    this.loading = true;
    
    this.accountService.changePassword(this.f.password_new.value).subscribe((_) => this.goBack());

  }
  goBack(): void{
    this.location.back();
  }

}
