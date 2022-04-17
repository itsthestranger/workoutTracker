import { Component, OnInit } from '@angular/core';
import { UserInformation } from '../user_information';
import { Observable, Subject } from "rxjs";
import { debounceTime, distinctUntilChanged, switchMap } from "rxjs/operators";
import { UserService } from '../user.service';

@Component({
  selector: 'app-user-search',
  templateUrl: './user-search.component.html',
  styleUrls: ['./user-search.component.css']
})
export class UserSearchComponent implements OnInit {
  users$!: Observable<UserInformation[]>;
  private searchTerms = new Subject<string>();
  constructor(private userService: UserService) { }

  ngOnInit(): void {
    this.users$ = this.searchTerms.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((term: string) => this.userService.searchUsers(term)),
    );
  }

  search(term: string): void{
    this.searchTerms.next(term);
  }

}
