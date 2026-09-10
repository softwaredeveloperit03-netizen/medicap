import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared.module';
import { ClientDocumentDeptComponent } from './client-document-dept.component';

@NgModule({
  declarations: [ClientDocumentDeptComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule,
    SharedModule,
    TranslateModule
  ],
  exports: [ClientDocumentDeptComponent]
})
export class ClientDocumentDeptModule {}
