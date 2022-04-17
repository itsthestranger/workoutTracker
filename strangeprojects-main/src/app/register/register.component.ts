import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AccountService } from '../account.service';
import { first } from 'rxjs/operators';
import { Cookie } from '../cookie';
import { flatten } from '@angular/compiler';
import { InputMatchValidator, CheckUsernameAvailability, CheckEmailAvailability } from "../custom-validators.validator";

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css']
})
export class RegisterComponent implements OnInit {

  form: FormGroup;
  loading = false;
  submitted = false;


  constructor(
    private formBuilder: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private accountService: AccountService,
  ) {
    this.form = this.formBuilder.group({
      username: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(40), Validators.pattern("^[a-zA-Z][a-zA-Z0-9_]*$")]], //start with letter, than lower-/uppercase letters, numbers and underscores are allowed
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6), Validators.maxLength(60), Validators.pattern("^(?=[^A-Z]*[A-Z])(?=\\D*\\d)[^:&.~\\s][a-zA-Z0-9]+$")]], //needs to have at least one uppercase letter and at least one number (special characters are not allowed)
      password_confirm: ['', Validators.required]
      },{
        validator: [
          InputMatchValidator('password', 'password_confirm'), 
          CheckUsernameAvailability(this.accountService, 'username'),
          CheckEmailAvailability(this.accountService, 'email'),
        ]
      }
    );
   }

  ngOnInit(): void {
  }

  get f(){
    return this.form.controls;
  }

  onSubmit():void{
    this.submitted = true;

    if(this.form.invalid){
      return;
    }
    this.loading = true;
    this.accountService.register(this.f.username.value, this.f.email.value, this.f.password.value)
      .subscribe(
        ret =>{
          //return something -> based on that error msg
          this.router.navigate(["/login"]);
        }
      );
  }

}
