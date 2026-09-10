import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PasswordPromptModalComponent } from './password-prompt-modal/password-prompt-modal.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
    imports: [ TranslateModule,CommonModule],
    declarations: [PasswordPromptModalComponent],
    exports: [PasswordPromptModalComponent]
})
export class ModalModule { }