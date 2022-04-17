import { Component, OnInit } from '@angular/core';
import { UserService } from '../user.service';
import { UserInformation } from '../user_information';
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from "@angular/common";
import { AppService } from '../app.service';
import { FilesService } from '../files.service';

@Component({
  selector: 'app-user-profile',
  templateUrl: './user-profile.component.html',
  styleUrls: ['./user-profile.component.css']
})
export class UserProfileComponent implements OnInit {

  user?: UserInformation;
  editing:boolean=false;

  fileToUpload: File | null = null;

  constructor(
    private userService: UserService,
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private location: Location,
    private appService: AppService,
    private filesService: FilesService
    ) { 
      this.appService.loggedInChange.subscribe(value => {
        if(!value){
          console.log("not logged in...");
          this.router.navigate(["/login"]);
        }
      });
    }

  ngOnInit(): void {
    //check for login
    //get UserInformation
    if(!localStorage.getItem('token')){
      this.router.navigate(["/login"]);
    }else{
      this.accountService.isLoggedIn().subscribe(ret => {
        if(!ret.loggedIn){
          this.router.navigate(["/login"]);
        }
      });
      this.getUserInformation();
    }
  }

  getUserInformation(){
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if(id == 0)
    {
      this.userService.getUserInformation().subscribe(information => this.user = information);
    }else{
      this.userService.getOtherUserInformation(id).subscribe(information => {
        this.user = information;
      });
    }
  }

  handleFileInput(event:Event) {
    const target= event.target as HTMLInputElement;
    const file: File = (target.files as FileList)[0];
    this.fileToUpload = file;
    
  }

  uploadImage(){
    if(this.fileToUpload){
      //upload image to server via service
      console.log(this.fileToUpload);
      this.filesService.uploadImage(this.fileToUpload).subscribe();
    }
  }

  updateProfile(){
    if(this.user){
      this.userService.updateProfile(this.user).subscribe();
    }
  }

  changePassword(){
    this.router.navigate(["/change_password"]);
  }

  goBack(): void{
    this.location.back();
  }


  toggleEditing(){
    if(this.editing){
      this.editing=false;
    }else{
      this.editing = true;
    }
  }

  toggleFollow(){
    if(this.user){
      if(this.user.following){
        this.userService.unfollow(this.user.id).subscribe((ret) => {
          if(this.user){
            this.user.following = false
          }
        });
      }else{
        this.userService.follow(this.user.id).subscribe((ret) => {
          if(this.user){
            this.user.following = true;
          }
        });
        
      }
    }
  }

}
