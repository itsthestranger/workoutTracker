import { FormGroup } from '@angular/forms';
import { AccountService } from "./account.service";

    

export function InputMatchValidator(input: string, matchingInput: string){

    return (formGroup: FormGroup) => {

        const inputVal = formGroup.controls[input];

        const matchingInputVal = formGroup.controls[matchingInput];

        if (matchingInputVal.errors && !matchingInputVal.errors.inputMatchValidator) {

            return;

        }

        if (inputVal.value !== matchingInputVal.value) {

            matchingInputVal.setErrors({ inputMatchValidator: true });

        } else {

            matchingInputVal.setErrors(null);

        }

    }

}

export function CheckUsernameAvailability(accountService: AccountService, username: string){

    return (formGroup: FormGroup) => {

        const usernameVal = formGroup.controls[username];
        console.log(`in checkusernameavailability username: ${usernameVal.value}`)
        accountService.checkUsernameAvailability(usernameVal.value)
            .subscribe((res) => {
                if (res.available){
                    usernameVal.setErrors(null)
                }else{
                    usernameVal.setErrors({ checkUsernameAvailabilityValidator: true})
                }
        })

        
    }
}

export function CheckEmailAvailability(accountService: AccountService, email: string){
    return (formGroup: FormGroup) => {

        const emailVal = formGroup.controls[email];
        console.log(`in checkemailavailability email: ${emailVal.value}`)
        accountService.checkEmailAvailability(emailVal.value)
            .subscribe((res) => {
                if (res.available){
                    emailVal.setErrors(null)
                }else{
                    emailVal.setErrors({ checkEmailAvailabilityValidator: true})
                }
        })

        
    }
    

}

export function CheckCurrentPassword(accountService: AccountService, password: string){
    return (formGroup: FormGroup) => {

        const passwordVal = formGroup.controls[password];
        console.log(`in CheckCurrentPassword password: ${passwordVal.value}`)
        accountService.checkPassword(passwordVal.value)
            .subscribe((passwrd) => {
                if (passwrd.correct){
                    passwordVal.setErrors(null)
                }else{
                    passwordVal.setErrors({ checkCurrentPasswordValidator: true})
                }
        })

        
    }
    

}