import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
 import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LogComponent } from './log/log.component';
import { IndexdocComponent } from './indexdoc/indexdoc.component';
import { TranslateModule } from '@ngx-translate/core';

 

const routes:Routes=[
  {path:'',component:LogComponent},
  {path:'new',component:IndexdocComponent}
  
]
 
@NgModule({
  declarations: [
    LogComponent,
    IndexdocComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MasterDocumentsModule { }
